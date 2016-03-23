<?php

namespace WikibaseQuality\ExternalValidation\Tests\DumpMetaInformation;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;

/**
 * @covers WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class DumpMetaInformationTest extends \MediaWikiTestCase {

	/**
	 * @var DumpMetaInformation
	 */
	private $dumpMetaInformation;

	public function __construct( $name = null, $data = array(), $dataName = null ) {
		parent::__construct( $name, $data, $dataName );

		// Create example dump meta information
		$this->dumpMetaInformation = new DumpMetaInformation(
			'foo',
			new ItemId( 'Q1' ),
			array( new PropertyId( 'P1' ) ),
			'20150101000000',
			'en',
			'http://www.foo.bar',
			42,
			new ItemId( 'Q6938433' )
		);
	}

	/**
	 * @dataProvider constructDataProvider
	 */
	public function testConstruct( $dumpId, $sourceItemId, $identifierPropertyIds, $importDate, $language, $sourceUrl, $size, $licenseItemId, $expectedException = null ) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$metaInformation = new DumpMetaInformation( $dumpId, $sourceItemId, $identifierPropertyIds, $importDate, $language, $sourceUrl, $size, $licenseItemId );

		$this->assertEquals( $sourceItemId, $metaInformation->getSourceItemId() );
		$this->assertEquals( $identifierPropertyIds, $metaInformation->getIdentifierPropertyIds() );
		$this->assertEquals( $importDate, $metaInformation->getImportDate() );
		$this->assertEquals( $language, $metaInformation->getLanguageCode() );
		$this->assertEquals( $sourceUrl, $metaInformation->getSourceUrl() );
		$this->assertEquals( $size, $metaInformation->getSize() );
		$this->assertEquals( $licenseItemId, $metaInformation->getLicenseItemId() );
	}

	/**
	 * Test cases for testConstruct
	 *
	 * @return array
	 */
	public function constructDataProvider() {
		$dumpId = 'foobar';
		$sourceItemId = new ItemId( 'Q123' );
		$identifierPropertyIds = array( new PropertyId( 'P123' ) );
		$importDate = '20150101000000';
		$language = 'de';
		$sourceUrl = 'http://randomurl.tld';
		$size = 42;
		$licenseItemId = new ItemId( 'Q6938433' );

		return array(
			array(
				$dumpId,
				$sourceItemId,
				$identifierPropertyIds,
				$importDate,
				$language,
				$sourceUrl,
				$size,
				$licenseItemId
			),
			array(
				42,
				$sourceItemId,
				$identifierPropertyIds,
				$importDate,
				$language,
				$sourceUrl,
				$size,
				$licenseItemId,
				InvalidArgumentException::class
			),
			array(
				$dumpId,
				$sourceItemId,
				$identifierPropertyIds,
				$importDate,
				42,
				$sourceUrl,
				$size,
				$licenseItemId,
				InvalidArgumentException::class
			),
			array(
				$dumpId,
				$sourceItemId,
				$identifierPropertyIds,
				$importDate,
				$language,
				42,
				$size,
				$licenseItemId,
				InvalidArgumentException::class
			),
			array(
				$dumpId,
				$sourceItemId,
				$identifierPropertyIds,
				$importDate,
				$language,
				$sourceUrl,
				'42',
				$licenseItemId,
				InvalidArgumentException::class
			),
			array(
				$dumpId,
				$sourceItemId,
				$identifierPropertyIds,
				42,
				$language,
				$sourceUrl,
				$size,
				$licenseItemId,
				InvalidArgumentException::class
			),
			array(
				$dumpId,
				$sourceItemId,
				$identifierPropertyIds,
				'foobar',
				$language,
				$sourceUrl,
				$size,
				$licenseItemId,
				InvalidArgumentException::class
			)
		);
	}

	public function testGetDumpId() {

		$this->assertEquals( $this->dumpMetaInformation->getDumpId(), 'foo' );
	}

	public function testGetSourceItemId() {

		$this->assertEquals( $this->dumpMetaInformation->getSourceItemId(), new ItemId( 'Q1' ) );
	}

	public function testGetImportDate() {

		$this->assertEquals( $this->dumpMetaInformation->getImportDate(), '20150101000000' );
	}

	public function testGetLanguage() {

		$this->assertEquals( $this->dumpMetaInformation->getLanguageCode(), 'en' );
	}

	public function testGetSourceUrl() {

		$this->assertEquals( $this->dumpMetaInformation->getSourceUrl(), 'http://www.foo.bar' );
	}

	public function testGetSize() {

		$this->assertEquals( $this->dumpMetaInformation->getSize(), 42 );
	}

	public function testGetLicenseItemId() {

		$this->assertEquals( $this->dumpMetaInformation->getLicenseItemId(), new ItemId( 'Q6938433' ) );
	}

}
