<?php

namespace WikibaseQuality\ExternalValidation\Tests\Serializer;


/**
 * @covers WikibaseQuality\ExternalValidation\Serializer\IndexedTagsSerializer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class IndexedTagsSerializerTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider constructDataProvider
	 */
	public function testConstruct( $shouldIndexTags, $expectedException = null ) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$serializer = $this->getMockBuilder( 'WikibaseQuality\ExternalValidation\Serializer\IndexedTagsSerializer' )
			->setConstructorArgs( array( $shouldIndexTags ) )
			->setMethods( array() )
			->getMockForAbstractClass();

		$this->assertEquals( $shouldIndexTags, $serializer->shouldIndexTags() );
	}

	/**
	 * Test cases for testConstruct
	 * @return array
	 */
	public function constructDataProvider() {
		return array(
			array(
				true
			),
			array(
				false
			),
			array(
				42,
				'InvalidArgumentException'
			)
		);
	}
}
