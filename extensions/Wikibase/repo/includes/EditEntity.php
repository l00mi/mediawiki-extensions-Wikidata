<?php

namespace Wikibase;

use Html;
use IContextSource;
use InvalidArgumentException;
use MWException;
use ReadOnlyError;
use RequestContext;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Hooks\EditFilterHookRunner;
use Wikibase\Repo\Store\EntityPermissionChecker;

/**
 * Handler for editing activity, providing a unified interface for saving modified entities while performing
 * permission checks and handling edit conflicts.
 *
 * @license GPL-2.0+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class EditEntity {

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var EntityTitleStoreLookup
	 */
	private $titleLookup;

	/**
	 * @var EntityStore
	 */
	private $entityStore;

	/**
	 * @var EntityPermissionChecker
	 */
	private $permissionChecker;

	/**
	 * @var EntityDiffer
	 */
	private $entityDiffer;

	/**
	 * @var EntityPatcher
	 */
	private $entityPatcher;

	/**
	 * The modified entity we are trying to save
	 *
	 * @var EntityDocument|null
	 */
	private $newEntity = null;

	/**
	 * @var EntityRevision|null
	 */
	private $baseRev = null;

	/**
	 * @var int|bool
	 */
	private $baseRevId;

	/**
	 * @var EntityRevision|null
	 */
	private $latestRev = null;

	/**
	 * @var int
	 */
	private $latestRevId = 0;

	/**
	 * @var Status|null
	 */
	private $status = null;

	/**
	 * @var User|null
	 */
	private $user = null;

	/**
	 * @var Title|null
	 */
	private $title = null;

	/**
	 * @var IContextSource
	 */
	private $context;

	/**
	 * @var EditFilterHookRunner
	 */
	private $editFilterHookRunner;

	/**
	 * @var int Bit field for error types, using the EditEntity::XXX_ERROR constants.
	 */
	private $errorType = 0;

	/**
	 * indicates a permission error
	 */
	const PERMISSION_ERROR = 1;

	/**
	 * indicates an unresolved edit conflict
	 */
	const EDIT_CONFLICT_ERROR = 2;

	/**
	 * indicates a token or session error
	 */
	const TOKEN_ERROR = 4;

	/**
	 * indicates that an error occurred while saving
	 */
	const SAVE_ERROR = 8;

	/**
	 * Indicates that the content failed some precondition to saving,
	 * such a a global uniqueness constraint.
	 */
	const PRECONDITION_FAILED = 16;

	/**
	 * Indicates that the content triggered an edit filter that uses
	 * the EditFilterMergedContent hook to supervise edits.
	 */
	const FILTERED = 32;

	/**
	 * Indicates that the edit exceeded a rate limit.
	 */
	const RATE_LIMIT = 64;

	/**
	 * bit mask for asking for any error.
	 */
	const ANY_ERROR = 0xFFFFFFFF;

	/**
	 * @var string[]
	 */
	private $requiredPermissions = array( 'edit' );

	/**
	 * @param EntityTitleStoreLookup $titleLookup
	 * @param EntityRevisionLookup $entityLookup
	 * @param EntityStore $entityStore
	 * @param EntityPermissionChecker $permissionChecker
	 * @param EntityDiffer $entityDiffer
	 * @param EntityPatcher $entityPatcher
	 * @param EntityDocument $newEntity the new entity object
	 * @param User $user the user performing the edit
	 * @param EditFilterHookRunner $editFilterHookRunner
	 * @param int|bool $baseRevId the base revision ID for conflict checking.
	 *        Defaults to false, disabling conflict checks.
	 *        `true` can be used to set the base revision to the latest revision:
	 *        This will detect "late" edit conflicts, i.e. someone squeezing in an edit
	 *        just before the actual database transaction for saving beings.
	 *        The empty string and 0 are both treated as `false`, disabling conflict checks.
	 * @param IContextSource|null $context the context to use while processing
	 *        the edit; defaults to RequestContext::getMain().
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityTitleStoreLookup $titleLookup,
		EntityRevisionLookup $entityLookup,
		EntityStore $entityStore,
		EntityPermissionChecker $permissionChecker,
		EntityDiffer $entityDiffer,
		EntityPatcher $entityPatcher,
		EntityDocument $newEntity,
		User $user,
		EditFilterHookRunner $editFilterHookRunner,
		$baseRevId = false,
		IContextSource $context = null
	) {
		$this->newEntity = $newEntity;

		if ( is_string( $baseRevId ) ) {
			$baseRevId = (int)$baseRevId;
		}

		if ( $baseRevId === 0 ) {
			$baseRevId = false;
		}

		$this->user = $user;
		$this->baseRevId = $baseRevId;

		$this->errorType = 0;
		$this->status = Status::newGood();

		if ( $context === null ) {
			$context = RequestContext::getMain();
		}

		$this->context = $context;

		$this->titleLookup = $titleLookup;
		$this->entityRevisionLookup = $entityLookup;
		$this->entityStore = $entityStore;
		$this->permissionChecker = $permissionChecker;
		$this->entityDiffer = $entityDiffer;
		$this->entityPatcher = $entityPatcher;

		$this->editFilterHookRunner = $editFilterHookRunner;
	}

	/**
	 * Returns the new entity object to be saved. May be different from the entity supplied
	 * to the constructor in case the entity was patched to resolve edit conflicts.
	 *
	 * @return EntityDocument
	 */
	public function getNewEntity() {
		return $this->newEntity;
	}

	/**
	 * Returns the Title of the page holding the entity that is being edited.
	 *
	 * @return Title|null
	 */
	private function getTitle() {
		if ( $this->title === null ) {
			$id = $this->newEntity->getId();

			if ( $id !== null ) {
				$this->title = $this->titleLookup->getTitleForId( $id );
			}
		}

		return $this->title;
	}

	/**
	 * Returns the latest revision of the entity.
	 *
	 * @return EntityRevision|null
	 */
	public function getLatestRevision() {
		if ( $this->latestRev === null ) {
			$id = $this->newEntity->getId();

			if ( $id !== null ) {
				// NOTE: It's important to remember this, if someone calls clear() on
				// $this->getPage(), this should NOT change!
				$this->latestRev = $this->entityRevisionLookup->getEntityRevision(
					$id,
					0,
					EntityRevisionLookup::LATEST_FROM_MASTER
				);
			}
		}

		return $this->latestRev;
	}

	/**
	 * @return int 0 if the entity doesn't exist
	 */
	private function getLatestRevisionId() {
		// Don't do negative caching: We call this to see whether the entity yet exists
		// before creating.
		if ( $this->latestRevId === 0 ) {
			$id = $this->newEntity->getId();

			if ( $this->latestRev !== null ) {
				$this->latestRevId = $this->latestRev->getRevisionId();
			} elseif ( $id !== null ) {
				$this->latestRevId = (int)$this->entityRevisionLookup->getLatestRevisionId(
					$id,
					EntityRevisionLookup::LATEST_FROM_MASTER
				);
			}
		}

		return $this->latestRevId;
	}

	/**
	 * Is the entity new?
	 * An entity is new in case it either doesn't have an id or the Title belonging
	 * to it doesn't (yet) exist.
	 *
	 * @return bool
	 */
	private function isNew() {
		return $this->newEntity->getId() === null || $this->getLatestRevisionId() === 0;
	}

	/**
	 * If no base revision was supplied to the constructor, this will return false.
	 * In the trivial non-conflicting case, this will be the same as $this->getLatestRevisionId().
	 *
	 * @return int|bool
	 */
	private function getBaseRevisionId() {
		if ( $this->baseRevId === null || $this->baseRevId === true ) {
			$this->baseRevId = $this->getLatestRevisionId();
		}

		return $this->baseRevId;
	}

	/**
	 * Returns the edits base revision.
	 * If no base revision was supplied to the constructor, this will return null.
	 * In the trivial non-conflicting case, this will be the same as $this->getLatestRevision().
	 *
	 * @return EntityRevision|null
	 * @throws MWException
	 */
	private function getBaseRevision() {
		if ( $this->baseRev === null ) {
			$baseRevId = $this->getBaseRevisionId();

			if ( $baseRevId === false ) {
				return null;
			} elseif ( $baseRevId === $this->getLatestRevisionId() ) {
				$this->baseRev = $this->getLatestRevision();
			} else {
				$id = $this->newEntity->getId();
				$this->baseRev = $this->entityRevisionLookup->getEntityRevision( $id, $baseRevId );

				if ( $this->baseRev === null ) {
					throw new MWException( 'Base revision ID not found: rev ' . $baseRevId
						. ' of ' . $id->getSerialization() );
				}
			}
		}

		return $this->baseRev;
	}

	/**
	 * Get the status object. Only defined after attemptSave() was called.
	 *
	 * After a successful save, the Status object's value field will contain an array,
	 * just like the status returned by WikiPage::doEditContent(). Well known fields
	 * in the status value are:
	 *
	 *  - new: bool whether the edit created a new page
	 *  - revision: Revision the new revision object
	 *  - errorFlags: bit field indicating errors, see the XXX_ERROR constants.
	 *
	 * @return Status|null
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Determines whether the last call to attemptSave was successful.
	 *
	 * @return bool false if attemptSave() failed, true otherwise
	 */
	public function isSuccess() {
		return $this->errorType === 0 && $this->status->isOK();
	}

	/**
	 * Checks whether this EditEntity encountered any of the given error types while executing attemptSave().
	 *
	 * @param int $errorType bit field using the EditEntity::XXX_ERROR constants.
	 *            Defaults to EditEntity::ANY_ERROR.
	 *
	 * @return bool true if this EditEntity encountered any of the error types in $errorType, false otherwise.
	 */
	public function hasError( $errorType = self::ANY_ERROR ) {
		return ( $this->errorType & $errorType ) !== 0;
	}

	/**
	 * Determines whether an edit conflict exists, that is, whether another user has edited the same item
	 * after the base revision was created.
	 *
	 * @return bool
	 */
	public function hasEditConflict() {
		return $this->doesCheckForEditConflicts()
			&& !$this->isNew()
			&& $this->getBaseRevisionId() !== $this->getLatestRevisionId();
	}

	/**
	 * Attempts to fix an edit conflict by patching the intended change into the latest revision after
	 * checking for conflicts. This modifies $this->newEntity but does not write anything to the
	 * database. Saving of the new content may still fail.
	 *
	 * @return bool True if the conflict could be resolved, false otherwise
	 */
	public function fixEditConflict() {
		$baseRev = $this->getBaseRevision();
		$latestRev = $this->getLatestRevision();

		if ( !$latestRev ) {
			wfLogWarning( 'Failed to load latest revision of entity ' . $this->newEntity->getId() . '! '
				. 'This may indicate entries missing from thw wb_entities_per_page table.' );
			return false;
		}

		// calculate patch against base revision
		// NOTE: will fail if $baseRev or $base are null, which they may be if
		// this gets called at an inappropriate time. The data flow in this class
		// should be improved.
		$patch = $this->entityDiffer->diffEntities( $baseRev->getEntity(), $this->newEntity );

		if ( $patch->isEmpty() ) {
			// we didn't technically fix anything, but if there is nothing to change,
			// so just keep the current content as it is.
			$this->newEntity = $latestRev->getEntity()->copy();
			return true;
		}

		// apply the patch( base -> new ) to the latest revision.
		$patchedLatest = $latestRev->getEntity()->copy();
		$this->entityPatcher->patchEntity( $patchedLatest, $patch );

		// detect conflicts against latest revision
		$cleanPatch = $this->entityDiffer->diffEntities( $latestRev->getEntity(), $patchedLatest );

		$conflicts = $patch->count() - $cleanPatch->count();

		if ( $conflicts > 0 ) {
			// patch doesn't apply cleanly
			if ( $this->userWasLastToEdit( $this->user, $this->newEntity->getId(), $this->getBaseRevisionId() ) ) {
				// it's a self-conflict
				if ( $cleanPatch->count() === 0 ) {
					// patch collapsed, possibly because of diff operation change from base to latest
					return false;
				} else {
					// we still have a working patch, try to apply
					$this->status->warning( 'wikibase-self-conflict-patched' );
				}
			} else {
				// there are unresolvable conflicts.
				return false;
			}
		} else {
			// can apply cleanly
			$this->status->warning( 'wikibase-conflict-patched' );
		}

		// remember the patched entity as the actual new entity to save
		$this->newEntity = $patchedLatest;

		return true;
	}

	/**
	 * Check if no edits were made by other users since the given revision.
	 * This makes the assumption that revision ids are monotonically increasing.
	 *
	 * @param User|null $user
	 * @param EntityId|null $entityId
	 * @param int|bool $lastRevId
	 *
	 * @return bool
	 */
	private function userWasLastToEdit( User $user = null, EntityId $entityId = null, $lastRevId = false ) {
		if ( $user === null || $entityId === null || $lastRevId === false ) {
			return false;
		}

		return $this->entityStore->userWasLastToEdit( $user, $entityId, $lastRevId );
	}

	/**
	 * Adds another permission (action) to be checked by checkEditPermissions().
	 * Per default, the 'edit' permission is checked.
	 *
	 * @param string $permission
	 */
	public function addRequiredPermission( $permission ) {
		$this->requiredPermissions[] = $permission;
	}

	/**
	 * Checks the necessary permissions to perform this edit.
	 * Per default, the 'edit' permission is checked.
	 * Use addRequiredPermission() to check more permissions.
	 */
	public function checkEditPermissions() {
		foreach ( $this->requiredPermissions as $action ) {
			$permissionStatus = $this->permissionChecker->getPermissionStatusForEntity(
				$this->user,
				$action,
				$this->newEntity );

			$this->status->merge( $permissionStatus );

			if ( !$this->status->isOK() ) {
				$this->errorType |= self::PERMISSION_ERROR;
				$this->status->fatal( 'no-permission' );
			}
		}
	}

	/**
	 * Checks if rate limits have been exceeded.
	 */
	private function checkRateLimits() {
		if ( $this->user->pingLimiter( 'edit' )
			|| ( $this->isNew() && $this->user->pingLimiter( 'create' ) )
		) {
			$this->errorType |= self::RATE_LIMIT;
			$this->status->fatal( 'actionthrottledtext' );
		}
	}

	/**
	 * Make sure the given WebRequest contains a valid edit token.
	 *
	 * @param string $token The token to check.
	 *
	 * @return bool true if the token is valid
	 */
	public function isTokenOK( $token ) {
		$tokenOk = $this->user->matchEditToken( $token );
		$tokenOkExceptSuffix = $this->user->matchEditTokenNoSuffix( $token );

		if ( !$tokenOk ) {
			if ( $tokenOkExceptSuffix ) {
				$this->status->fatal( 'token_suffix_mismatch' );
			} else {
				$this->status->fatal( 'session_fail_preview' );
			}

			$this->errorType |= self::TOKEN_ERROR;
			return false;
		}

		return true;
	}

	/**
	 * Attempts to save the new entity content, chile first checking for permissions, edit conflicts, etc.
	 *
	 * @param string $summary The edit summary.
	 * @param int         $flags      The EDIT_XXX flags as used by WikiPage::doEditContent().
	 *        Additionally, the EntityContent::EDIT_XXX constants can be used.
	 * @param string|bool $token Edit token to check, or false to disable the token check.
	 *                                Null will fail the token text, as will the empty string.
	 * @param bool|null $watch        Whether the user wants to watch the entity.
	 *                                Set to null to apply default according to getWatchDefault().
	 *
	 * @throws ReadOnlyError
	 * @return Status Indicates success and provides detailed warnings or error messages. See
	 *         getStatus() for more details.
	 * @see    WikiPage::doEditContent
	 */
	public function attemptSave( $summary, $flags, $token, $watch = null ) {
		if ( wfReadOnly() ) {
			throw new ReadOnlyError();
		}

		if ( $watch === null ) {
			$watch = $this->getWatchDefault();
		}

		$this->status = Status::newGood();
		$this->errorType = 0;

		if ( $token !== false && !$this->isTokenOK( $token ) ) {
			//@todo: This is redundant to the error code set in isTokenOK().
			//       We should figure out which error codes the callers expect,
			//       and only set the correct error code, in one place, probably here.
			$this->errorType |= self::TOKEN_ERROR;
			$this->status->fatal( 'sessionfailure' );
			$this->status->setResult( false, array( 'errorFlags' => $this->errorType ) );
			return $this->status;
		}

		$this->checkEditPermissions();

		$this->checkRateLimits(); // modifies $this->status

		if ( !$this->status->isOK() ) {
			$this->status->setResult( false, array( 'errorFlags' => $this->errorType ) );
			return $this->status;
		}

		//NOTE: Make sure the latest revision is loaded and cached.
		//      Would happen on demand anyway, but we want a well-defined point at which "latest" is frozen
		//      to a specific revision, just before the first check for edit conflicts.
		$this->getLatestRevision();
		$this->getLatestRevisionId();

		$this->applyPreSaveChecks(); // modifies $this->status

		if ( !$this->status->isOK() ) {
			$this->errorType |= self::PRECONDITION_FAILED;
		}

		if ( !$this->status->isOK() ) {
			$this->status->setResult( false, array( 'errorFlags' => $this->errorType ) );
			return $this->status;
		}

		$hookStatus = $this->editFilterHookRunner->run( $this->newEntity, $this->user, $summary );
		if ( !$hookStatus->isOK() ) {
			$this->errorType |= self::FILTERED;
		}
		$this->status->merge( $hookStatus );

		if ( !$this->status->isOK() ) {
			$this->status->setResult( false, array( 'errorFlags' => $this->errorType ) );
			return $this->status;
		}

		try {
			$entityRevision = $this->entityStore->saveEntity(
				$this->newEntity,
				$summary,
				$this->user,
				$flags | EDIT_AUTOSUMMARY,
				$this->doesCheckForEditConflicts() ? $this->getLatestRevisionId() : false
			);

			$editStatus = Status::newGood( array( 'revision' => $entityRevision ) );
		} catch ( StorageException $ex ) {
			$editStatus = $ex->getStatus();

			if ( $editStatus === null ) {
				// XXX: perhaps internalerror_info isn't the best, but we need some generic error message.
				$editStatus = Status::newFatal( 'internalerror_info', $ex->getMessage() );
			}

			$this->errorType |= self::SAVE_ERROR;
		}

		$this->status->setResult( $editStatus->isOK(), $editStatus->getValue() );
		$this->status->merge( $editStatus );

		if ( $this->status->isOK() ) {
			$this->updateWatchlist( $watch );
		} else {
			$value = $this->status->getValue();
			$value['errorFlags'] = $this->errorType;
			$this->status->setResult( false, $value );
		}

		return $this->status;
	}

	private function applyPreSaveChecks() {
		if ( $this->hasEditConflict() ) {
			if ( !$this->fixEditConflict() ) {
				$this->status->fatal( 'edit-conflict' );
				$this->errorType |= self::EDIT_CONFLICT_ERROR;

				return $this->status;
			}
		}

		// FIXME: Why is this dummy call here?
		$this->getBaseRevision();

		return $this->status;
	}

	/**
	 * Whether this EditEntity will check for edit conflicts
	 *
	 * @return bool
	 */
	public function doesCheckForEditConflicts() {
		return $this->getBaseRevisionId() !== false;
	}

	/**
	 * Shows an error page showing the errors that occurred during attemptSave(), if any.
	 *
	 * If $titleMessage is set it is made an assumption that the page is still the original
	 * one, and there should be no link back from a special error page.
	 *
	 * @param string|null $titleMessage Message key for the page title.
	 *
	 * @return bool true if an error page was shown, false if there were no errors to show.
	 */
	public function showErrorPage( $titleMessage = null ) {
		$out = $this->context->getOutput();

		if ( $this->status === null || $this->status->isOK() ) {
			return false;
		}

		if ( $titleMessage === null ) {
			$out->prepareErrorPage( wfMessage( 'errorpagetitle' ) );
		} else {
			$out->prepareErrorPage( wfMessage( $titleMessage ), wfMessage( 'errorpagetitle' ) );
		}

		$this->showStatus();

		if ( !isset( $titleMessage ) ) {
			$out->returnToMain( '', $this->getTitle() );
		}

		return true;
	}

	/**
	 * Shows any errors or warnings from attemptSave().
	 *
	 * @return bool true if any message was shown, false if there were no errors to show.
	 */
	private function showStatus() {
		if ( $this->status === null || $this->status->isGood() ) {
			return false;
		}

		$out = $this->context->getOutput();
		$text = $this->status->getHTML();

		$out->addHTML( Html::rawElement( 'div', array( 'class' => 'error' ), $text ) );

		return true;
	}

	/**
	 * Returns whether the present edit would, per default,
	 * lead to the user watching the page.
	 *
	 * This uses the user's watchdefault and watchcreations settings
	 * and considers whether the entity is already watched by the user.
	 *
	 * @note Keep in sync with logic in EditPage!
	 *
	 * @return bool
	 */
	private function getWatchDefault() {
		// User wants to watch all edits or all creations.
		if ( $this->user->getOption( 'watchdefault' )
			|| ( $this->user->getOption( 'watchcreations' ) && $this->isNew() )
		) {
			return true;
		}

		// keep current state
		return !$this->isNew() && $this->entityStore->isWatching( $this->user, $this->newEntity->getId() );
	}

	/**
	 * Watches or unwatches the entity.
	 *
	 * @note Keep in sync with logic in EditPage!
	 * @todo: move to separate service
	 *
	 * @param bool $watch whether to watch or unwatch the page.
	 *
	 * @throws MWException
	 */
	private function updateWatchlist( $watch ) {
		if ( $this->getTitle() === null ) {
			throw new MWException( 'Title not yet known!' );
		}

		$this->entityStore->updateWatchlist( $this->user, $this->newEntity->getId(), $watch );
	}

}
