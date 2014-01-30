<?php

namespace Wikibase\Test;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Entity;
use Wikibase\Claim;

/**
 * @covers Wikibase\ItemView
 *
 * @since 0.1
 *
 * @group Wikibase
 * @group WikibaseItemView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 */
class ItemViewTest extends EntityViewTest {

	protected function getEntityViewClass() {
		return 'Wikibase\ItemView';
	}

	/**
	 * @param EntityId $id
	 * @param Claim[] $claims
	 *
	 * @return Entity
	 */
	protected function makeEntity( EntityId $id, $claims = array() ) {
		return $this->makeItem( $id, $claims );
	}

	/**
	 * Generates a suitable entity ID based on $n.
	 *
	 * @param int|string $n
	 *
	 * @return EntityId
	 */
	protected function makeEntityId( $n ) {
		return new ItemId( "Q$n");
	}
}
