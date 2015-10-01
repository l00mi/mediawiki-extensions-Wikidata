<?php

namespace WikibaseQuality\ExternalValidation\Serializer;

use ApiResult;
use Serializers\Serializer;

/**
 * @package WikibaseQuality\ExternalValidation\Serializer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
abstract class IndexedTagsSerializer implements Serializer {

	/**
	 * In case the array contains indexed values (in addition to named),
	 * give all indexed values the given tag name in XML output.
	 * This function MUST be called on every array that has numerical indexes.
	 *
	 * @param array &$arr
	 * @param string $tag
	 */
	protected function setIndexedTagName( array &$arr, $tag ) {
		ApiResult::setIndexedTagName( $arr, $tag );
	}

	/**
	 * In case the array is indexed by an ID, put the key into a specific
	 * tag attribute in XML output, and use the specified tag name.
	 * This function SHOULD be called on every array that is indexed by ID.
	 *
	 * @param array &$arr
	 * @param string $tagName
	 * @param string $idAttribute
	 */
	protected function setKeyAttributeName( array &$arr, $tagName, $idAttribute ) {
		ApiResult::setArrayType( $arr, 'kvp', $idAttribute );
		ApiResult::setIndexedTagName( $arr, $tagName );
	}

}
