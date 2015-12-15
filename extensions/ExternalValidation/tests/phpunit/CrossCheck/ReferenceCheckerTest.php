<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use WikibaseQuality\ExternalValidation\CrossCheck\ReferenceChecker;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult;

/**
 * @covers WikibaseQuality\ExternalValidation\CrossCheck\ReferenceChecker
 *
 * @group WikibaseQualityExternalValidation
 *
 * @uses   WikibaseQuality\ExternalValidation\CrossCheck\Result\ReferenceResult
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class ReferenceCheckerTest extends \MediaWikiTestCase {

	/**
	 * @var ReferenceChecker
	 */
	private $referenceHandler;

	protected function setUp() {
		parent::setUp();

		$this->referenceHandler = new ReferenceChecker();
	}

	protected function tearDown() {
		unset( $this->referenceHandler );

		parent::tearDown();
	}

	public function testConstructInvalidArguments() {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->referenceHandler->checkForReferences(
			$this->getMockBuilder( 'Wikibase\DataModel\Statement\Statement' )->disableOriginalConstructor()->getMock(),
			new PropertyId( 'P42' ),
			42,
			$this->getDumpMetaInformationMock( new ItemId( 'Q42' ) )
		);
	}

	/**
	 * @dataProvider executeDataProvider
	 */
	public function testExecute( $statement, $externalId, $identifierPropertyId, $dumpMetaInformation, $expectedResult ) {
		$actualResult = $this->referenceHandler->checkForReferences( $statement, $identifierPropertyId, $externalId, $dumpMetaInformation );

		$this->assertEquals( $expectedResult, $actualResult );

	}

	/**
	 * Test cases for testExecute
	 * @return array
	 */
	public function executeDataProvider() {
		// Create argument
		$statementWithoutReference = new Statement(
			new PropertyValueSnak(
				new PropertyId( 'P42' ),
				new StringValue( 'foobar' )
			)
		);
		$statementWithReference = new Statement(
			new PropertyValueSnak(
				new PropertyId( 'P42' ),
				new StringValue( 'foobar' )
			)
		);
		$statementWithReference->addNewReference(
			new PropertyValueSnak(
				new PropertyId( 'P84' ),
				new StringValue( 'foobar' )
			)
		);
		$identifierPropertyId = new PropertyId( 'P227' );
		$externalId = 'foobar';
		$dumpMetaInformation = $this->getDumpMetaInformationMock( new ItemId( 'Q42' ) );

		// Create expected result
		$referenceSnaks = new SnakList(
			array(
				new PropertyValueSnak(
					new PropertyId( STATED_IN_PID ),
					new EntityIdValue( $dumpMetaInformation->getSourceItemId() )
				),
				new PropertyValueSnak(
					$identifierPropertyId,
					new StringValue( $externalId )
				)
			)
		);
		$expectedReference = new Reference( $referenceSnaks );

		return array(
			array(
				$statementWithoutReference,
				$externalId,
				$identifierPropertyId,
				$dumpMetaInformation,
				new ReferenceResult(
					ReferenceResult::STATUS_REFERENCES_MISSING,
					$expectedReference
				)
			),
			array(
				$statementWithReference,
				$externalId,
				$identifierPropertyId,
				$dumpMetaInformation,
				new ReferenceResult(
					ReferenceResult::STATUS_REFERENCES_STATED,
					$expectedReference
				)
			)
		);
	}

	private function getDumpMetaInformationMock( ItemId $sourceItemId ) {
		$mock = $this->getMockBuilder( 'WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation' )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'getSourceItemId' )
			->will( $this->returnValue( $sourceItemId ) );

		return $mock;
	}

}
