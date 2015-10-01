<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck\Comparer;

use Wikibase\StringNormalizer;
use WikibaseQuality\ExternalValidation\CrossCheck\Comparer\StringComparer;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;

/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\Comparer\StringComparer
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class StringComparerTest extends \MediaWikiTestCase {

	/**
	 * @var StringComparer
	 */
	private $stringComparer;

	public function setUp() {
		parent::setUp();
		$this->stringComparer = new StringComparer( new StringNormalizer() );
	}

	public function tearDown() {
		unset( $this->stringComparer );

		parent::tearDown();
	}

	/**
	 * @dataProvider compareValidArgumentsDataProvider
	 */
	public function testCompareValidArguments( $value, $comparativeValue, $expectedResult ) {
		$actualResult = $this->stringComparer->compare( $value, $comparativeValue );

		$this->assertEquals( $expectedResult, $actualResult );
	}

	/**
	 * Test cases for testCompareValidArguments
	 * @return array
	 */
	public function compareValidArgumentsDataProvider() {
		return array(
			array(
				'foobar',
				'foobar',
				ComparisonResult::STATUS_MATCH
			),
			array(
				'foobar',
				'Foobar',
				ComparisonResult::STATUS_MATCH
			),
			array(
				'foobar',
				'foObar',
				ComparisonResult::STATUS_MATCH
			),
			array(
				'foobar',
				'FOOBAR',
				ComparisonResult::STATUS_MATCH
			),
			array(
				'foobar',
				'    foobar',
				ComparisonResult::STATUS_MATCH
			),
			array(
				'foobar',
				'foobar    ',
				ComparisonResult::STATUS_MATCH
			),
			array(
				'    foobar',
				'    foobar    ',
				ComparisonResult::STATUS_MATCH
			),
			array(
				'Emily Brontë',
				'Emily Brontë',
				ComparisonResult::STATUS_MATCH
			),
			array(
				'Richard von Weizsäcker',
				'Richard von Weizsäcker',
				ComparisonResult::STATUS_MATCH
			),
			// prefix/suffix partial match
			array(
				'foobar',
				'foobaz',
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			array(
				'fooba',
				'foobar',
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			array(
				'foobar',
				'fooba',
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			array(
				'foobar',
				'goobar',
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			array(
				'oobar',
				'foobar',
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			array(
				'foobar',
				'oobar',
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			array(
				'New York City',
				'New York City, NY',
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			// levenshtein partial match
			array(
				'foobar',
				'fooobar',
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			array(
				'fobar',
				'foobar',
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			array(
				'foubar',
				'foobar',
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			array(
				'Schlossstraße',
				'Schloßstraße',
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			array(
				'Yoko Ono',
				'Yōko Ono',
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			// mismatches
			array(
				'fo',
				'foobar',
				ComparisonResult::STATUS_MISMATCH
			),
			array(
				'obar',
				'foobar',
				ComparisonResult::STATUS_MISMATCH
			),
			array(
				'foo',
				'baz',
				ComparisonResult::STATUS_MISMATCH
			),
			array(
				'Johanna',
				'Johanna von Österreich',
				ComparisonResult::STATUS_MISMATCH
			),
			array(
				'Johanna von Österreich',
				'Johanna',
				ComparisonResult::STATUS_MISMATCH
			),
			array(
				'New York',
				'New York, NY',
				ComparisonResult::STATUS_MISMATCH
			)
		);
	}

	/**
	 * @dataProvider compareInvalidArgumentsDataProvider
	 */
	public function testCompareInvalidArguments( $value, $comparativeValue ) {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->stringComparer->compare( $value, $comparativeValue );
	}

	/**
	 * Test cases for testCompareInvalidArguments
	 * @return array
	 */
	public function compareInvalidArgumentsDataProvider() {
		return array(
			array(
				'foobar',
				42
			),
			array(
				42,
				'foobar'
			),
			array(
				42,
				42
			)
		);
	}

	/**
	 * @dataProvider compareWithArrayValidArgumentsDataProvider
	 */
	public function testCompareWithArrayValidArguments( $value, $comparativeValues, $expectedResult ) {
		$actualResult = $this->stringComparer->compareWithArray( $value, $comparativeValues );

		$this->assertEquals( $expectedResult, $actualResult );
	}

	/**
	 * Test cases for testCompareWithArrayValidArguments
	 *
	 * @return array
	 */
	public function compareWithArrayValidArgumentsDataProvider() {
		return array(
			array(
				'foobar',
				array( 'foobar', 'fo' ),
				ComparisonResult::STATUS_MATCH
			),
			array(
				'foobar',
				array( 'fo', 'foobar' ),
				ComparisonResult::STATUS_MATCH
			),
			array(
				'foobar',
				array( 'fo', 'FOOBAR' ),
				ComparisonResult::STATUS_MATCH
			),
			array(
				'foobar',
				array( 'fo', '   FOOBAR   ' ),
				ComparisonResult::STATUS_MATCH
			),
			array(
				'foobar',
				array( 'goobaz', 'foobaz' ),
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			array(
				'foobar',
				array( 'foobaz', 'goobaz' ),
				ComparisonResult::STATUS_PARTIAL_MATCH
			),
			array(
				'foobar',
				array( 'goo', 'baz' ),
				ComparisonResult::STATUS_MISMATCH
			),
			array(
				'foobar',
				array( 'baz', 'goo' ),
				ComparisonResult::STATUS_MISMATCH
			)
		);
	}

	/**
	 * @dataProvider compareWithArrayInvalidArgumentsDataProvider
	 */
	public function testCompareWithArrayInvalidArguments( $value, $comparativeValues ) {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->stringComparer->compareWithArray( $value, $comparativeValues );
	}

	/**
	 * Test cases for testCompareWithArrayInvalidArguments
	 *
	 * @return array
	 */
	public function compareWithArrayInvalidArgumentsDataProvider() {
		return array(
			array(
				'foobar',
				array( 42 )
			),
			array(
				42,
				array( 'foobar' )
			),
			array(
				42,
				array( 42 )
			)
		);
	}

}
