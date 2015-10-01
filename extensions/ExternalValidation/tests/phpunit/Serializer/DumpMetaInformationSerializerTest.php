<?php

namespace WikibaseQuality\ExternalValidation\Tests\Serializer;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;
use WikibaseQuality\ExternalValidation\Serializer\DumpMetaInformationSerializer;

/**
 * @covers WikibaseQuality\ExternalValidation\Serializer\DumpMetaInformationSerializer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @uses   WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class DumpMetaInformationSerializerTest extends SerializerTestBase {

	protected function buildSerializer() {
		return new DumpMetaInformationSerializer();
	}

	public function serializableProvider() {
		return array(
			array(
				new DumpMetaInformation(
					'foobar',
					new ItemId( 'Q36578' ),
					array( new PropertyId( 'P42' ) ),
					'20150101000000',
					'en',
					'http://www.foo.bar',
					42,
					new ItemId( 'Q6938433' )
				)
			)
		);
	}

	public function nonSerializableProvider() {
		return array(
			array(
				42
			),
			array(
				array()
			)
		);
	}

	public function serializationProvider() {
		$dumpMetaInformation = new DumpMetaInformation(
			'foobar',
			new ItemId( 'Q36578' ),
			array( new PropertyId( 'P42' ) ),
			'20150101000000',
			'en',
			'http://www.foo.bar',
			42,
			new ItemId( 'Q6938433' )
		);

		return array(
			array(
				array(
					'dumpId' => 'foobar',
					'sourceItemId' => 'Q36578',
					'identifierPropertyIds' => array(
						0 => 'P42',
						'_element' => 'propertyId'
					),
					'importDate' => '2015-01-01T00:00:00Z',
					'language' => 'en',
					'sourceUrl' => 'http://www.foo.bar',
					'size' => 42,
					'licenseItemId' => 'Q6938433'
				),
				$dumpMetaInformation
			)
		);
	}

	/**
	 * @return array an array of array( JSON, object to serialize)
	 */
	public function serializationJSONProvider() {
		return array(
			array(
				'{'
				. '    "dumpId": "foobar",'
				. '    "sourceItemId": "Q36578",'
				. '    "identifierPropertyIds": ['
				. '        "P42"'
				. '    ],'
				. '    "importDate": "2015-01-01T00:00:00Z",'
				. '    "language": "en",'
				. '    "sourceUrl": "http:\/\/www.foo.bar",'
				. '    "size": 42,'
				. '    "licenseItemId": "Q6938433"'
				. '}',
				new DumpMetaInformation(
					'foobar',
					new ItemId( 'Q36578' ),
					array( new PropertyId( 'P42' ) ),
					'20150101000000',
					'en',
					'http://www.foo.bar',
					42,
					new ItemId( 'Q6938433' )
				)
			),
		);
	}

	/**
	 * @return array an array of array( XML, object to serialize)
	 */
	public function serializationXMLProvider() {
		return array(
			array(
				'<api'
				. '    dumpId="foobar"'
				. '    sourceItemId="Q36578"'
				. '    importDate="2015-01-01T00:00:00Z"'
				. '    language="en"'
				. '    sourceUrl="http://www.foo.bar"'
				. '    size="42"'
				. '    licenseItemId="Q6938433"'
				. '>'
				. '    <identifierPropertyIds>'
				. '      <propertyId>P42</propertyId>'
				. '    </identifierPropertyIds>'
				. '</api>',
				new DumpMetaInformation(
					'foobar',
					new ItemId( 'Q36578' ),
					array( new PropertyId( 'P42' ) ),
					'20150101000000',
					'en',
					'http://www.foo.bar',
					42,
					new ItemId( 'Q6938433' )
				)
			),
		);
	}

}
