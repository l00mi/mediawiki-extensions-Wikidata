<?php

namespace WikibaseQuality\ExternalValidation\Serializer;

use ApiResult;
use InvalidArgumentException;
use Serializers\Serializer;


/**
 * Class IndexedTagsSerializer
 *
 * @package WikibaseQuality\ExternalValidation\Serializer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
abstract class IndexedTagsSerializer implements Serializer {

	/**
	 * Determines, if tags should be indexed.
	 * @var bool
	 */
	private $shouldIndexTags;

	/**
	 * @param bool $shouldIndexTags
	 */
	public function __construct( $shouldIndexTags = false ) {
		if ( !is_bool( $shouldIndexTags ) ) {
			throw new InvalidArgumentException( '$shouldIndexTags must be boolean.' );
		}

		$this->shouldIndexTags = $shouldIndexTags;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param mixed $object
	 * @return array|int|string|bool|float A possibly nested structure consisting of only arrays and scalar values
	 */
	abstract public function serialize( $object );

	/**
	 * @return bool
	 */
	public function shouldIndexTags() {
		return $this->shouldIndexTags;
	}

	/**
	 * In case the array contains indexed values (in addition to named),
	 * give all indexed values the given tag name. This function MUST be
	 * called on every array that has numerical indexes.
	 *
	 * @param array $arr
	 * @param string $tag
	 */
	protected function setIndexedTagName( array &$arr, $tag ) {
		if ( $this->shouldIndexTags() ) {
			ApiResult::setIndexedTagName( $arr, $tag );
		}
	}
}