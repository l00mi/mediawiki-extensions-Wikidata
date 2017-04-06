<?php

namespace WikibaseQuality\ExternalValidation\Tests\CrossCheck;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use WikibaseQuality\ExternalValidation\CrossCheck\CrossChecker;
use WikibaseQuality\ExternalValidation\CrossCheck\CrossCheckInteractor;
use WikibaseQuality\Tests\Helper\JsonFileEntityLookup;

/**
 * @covers \WikibaseQuality\ExternalValidation\CrossCheck\CrossCheckInteractor
 *
 * @group WikibaseQualityExternalValidation
 *
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class CrossCheckInteractorTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Item[]
	 */
	private $items;

	/**
	 * @var CrossCheckInteractor
	 */
	private $crossCheckInteractor;

	public function __construct( $name = null, array $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$entityLookup = new JsonFileEntityLookup( __DIR__ . '/testdata' );
		$this->items = array(
			'Q1' => $entityLookup->getEntity( new ItemId( 'Q1' ) ),
			'Q2' => $entityLookup->getEntity( new ItemId( 'Q2' ) ),
			'Q3' => $entityLookup->getEntity( new ItemId( 'Q3' ) )
		);
	}

	protected function setUp() {
		parent::setUp();

		$entityLookup = new JsonFileEntityLookup( __DIR__ . '/testdata' );
		$guidParser = new StatementGuidParser( new BasicEntityIdParser() );
		$crossChecker = $this->getMockBuilder( CrossChecker::class )
			->disableOriginalConstructor()
			->setMethods( array( 'crossCheckStatements' ) )
			->getMock();
		$crossChecker->expects( $this->any() )
			->method( 'crossCheckStatements' )
			->will( $this->returnCallback(
				function ( StatementList $entityStatements, StatementList $statements ) {
					return array_map(
						function ( Statement $statement ) {
							return $statement->getGuid();
						},
						$statements->toArray()
					);
				}
			) );
		$this->crossCheckInteractor = new CrossCheckInteractor( $entityLookup, $guidParser, $crossChecker );
	}

	protected function tearDown() {
		unset( $this->crossCheckInteractor );

		parent::tearDown();
	}

	/**
	 * @dataProvider crossCheckEntityByIdDataProvider
	 */
	public function testCrossCheckEntityById( ItemId $itemId, array $expectedResult = null ) {
		$actualResult = $this->crossCheckInteractor->crossCheckEntityById( $itemId );

		$this->runAssertions( $expectedResult, $actualResult );
	}

	/**
	 * Test cases for testCrossCheckEntityById
	 * @return array
	 */
	public function crossCheckEntityByIdDataProvider() {
		return array(
			array(
				new ItemId( 'Q1' ),
				array(
					'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
					'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
					'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5',
					'Q1$27ba9958-7151-4673-8956-f8f1d8648d1e',
					'Q1$91f37b64-5ceb-4639-affe-807e871d181c',
					'Q1$4575e0f2-39bf-4256-a08e-36861930119c',
					'Q1$035ceedb-2982-4a34-8c7c-36c766cfcc62',
					'Q1$45ff2dd8-05d7-4952-a94d-1f24aaebff78'
				)
			),
			array(
				new ItemId( 'Q2' ),
				array(
					'Q2$0adcfe9e-cda1-4f74-bc98-433150e49b53',
					'Q2$07c00375-1be7-43a6-ac97-32770f2bb5ac',
					'Q2$bafab2e3-ee12-4d61-a482-1e8f322135fc',
					'Q2$89d68786-17dc-4315-8173-721522cadef2'
				)
			),
			array(
				new ItemId( 'Q6' ),
				null
			)
		);
	}

	/**
	 * @dataProvider crossCheckEntitiesByIdsDataProvider
	 */
	public function testCrossCheckEntitiesByIds(
		array $entityIds,
		array $expectedResult = null,
		$expectedException = null
	) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$actualResult = $this->crossCheckInteractor->crossCheckEntitiesByIds( $entityIds );

		$this->runAssertions( $expectedResult, $actualResult );
	}

	/**
	 * Test cases for testCrossCheckEntitiesByIds
	 * @return array
	 */
	public function crossCheckEntitiesByIdsDataProvider() {
		return array(
			array(
				array(
					new ItemId( 'Q1' ),
					new ItemId( 'Q2' )
				),
				array(
					'Q1' => array(
						'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
						'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
						'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5',
						'Q1$27ba9958-7151-4673-8956-f8f1d8648d1e',
						'Q1$91f37b64-5ceb-4639-affe-807e871d181c',
						'Q1$4575e0f2-39bf-4256-a08e-36861930119c',
						'Q1$035ceedb-2982-4a34-8c7c-36c766cfcc62',
						'Q1$45ff2dd8-05d7-4952-a94d-1f24aaebff78'
					),
					'Q2' => array(
						'Q2$0adcfe9e-cda1-4f74-bc98-433150e49b53',
						'Q2$07c00375-1be7-43a6-ac97-32770f2bb5ac',
						'Q2$bafab2e3-ee12-4d61-a482-1e8f322135fc',
						'Q2$89d68786-17dc-4315-8173-721522cadef2'
					)
				)
			),
			array(
				array(
					'Q1',
					'Q2'
				),
				null,
				InvalidArgumentException::class
			)
		);
	}

	/**
	 * @dataProvider crossCheckEntityDataProvider
	 */
	public function testCrossCheckEntity( StatementList $statements, array $expectedResult ) {
		$actualResult = $this->crossCheckInteractor->crossCheckStatements( $statements );

		$this->runAssertions( $expectedResult, $actualResult );
	}

	/**
	 * Test cases for testCrossCheckEntity
	 * @return array
	 */
	public function crossCheckEntityDataProvider() {
		return array(
			array(
				$this->items['Q1']->getStatements(),
				array(
					'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
					'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
					'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5',
					'Q1$27ba9958-7151-4673-8956-f8f1d8648d1e',
					'Q1$91f37b64-5ceb-4639-affe-807e871d181c',
					'Q1$4575e0f2-39bf-4256-a08e-36861930119c',
					'Q1$035ceedb-2982-4a34-8c7c-36c766cfcc62',
					'Q1$45ff2dd8-05d7-4952-a94d-1f24aaebff78'
				)
			),
			array(
				$this->items['Q2']->getStatements(),
				array(
					'Q2$0adcfe9e-cda1-4f74-bc98-433150e49b53',
					'Q2$07c00375-1be7-43a6-ac97-32770f2bb5ac',
					'Q2$bafab2e3-ee12-4d61-a482-1e8f322135fc',
					'Q2$89d68786-17dc-4315-8173-721522cadef2'
				)
			)
		);
	}

	/**
	 * @dataProvider crossCheckEntitiesDataProvider
	 */
	public function testCrossCheckEntities(
		array $entities,
		array $expectedResult = null,
		$expectedException = null
	) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$actualResult = $this->crossCheckInteractor->crossCheckEntities( $entities );

		$this->runAssertions( $expectedResult, $actualResult );
	}

	/**
	 * Test cases for testCrossCheckEntities
	 * @return array
	 */
	public function crossCheckEntitiesDataProvider() {
		return array(
			array(
				array(
					$this->items['Q1'],
					$this->items['Q2']
				),
				array(
					'Q1' => array(
						'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
						'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
						'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5',
						'Q1$27ba9958-7151-4673-8956-f8f1d8648d1e',
						'Q1$91f37b64-5ceb-4639-affe-807e871d181c',
						'Q1$4575e0f2-39bf-4256-a08e-36861930119c',
						'Q1$035ceedb-2982-4a34-8c7c-36c766cfcc62',
						'Q1$45ff2dd8-05d7-4952-a94d-1f24aaebff78'
					),
					'Q2' => array(
						'Q2$0adcfe9e-cda1-4f74-bc98-433150e49b53',
						'Q2$07c00375-1be7-43a6-ac97-32770f2bb5ac',
						'Q2$bafab2e3-ee12-4d61-a482-1e8f322135fc',
						'Q2$89d68786-17dc-4315-8173-721522cadef2'
					)
				)
			),
			array(
				array(
					'Q1',
					'Q2'
				),
				null,
				InvalidArgumentException::class
			)
		);
	}

	/**
	 * @dataProvider crossCheckEntityByIdWithPropertiesDataProvider
	 */
	public function testCrossCheckEntityByIdWithProperties(
		ItemId $itemId,
		array $propertyIds,
		array $expectedResult = null,
		$expectedException = null
	) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$actualResult = $this->crossCheckInteractor->crossCheckEntityByIdWithProperties( $itemId, $propertyIds );

		$this->runAssertions( $expectedResult, $actualResult );
	}

	/**
	 * Test cases for testCrossCheckEntityByIdWithProperties
	 * @return array
	 */
	public function crossCheckEntityByIdWithPropertiesDataProvider() {
		return array(
			array(
				new ItemId( 'Q1' ),
				array(
					new PropertyId( 'P1' )
				),
				array(
					'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
					'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7'
				)
			),
			array(
				new ItemId( 'Q1' ),
				array(
					new PropertyId( 'P1' ),
					new PropertyId( 'P2' )
				),
				array(
					'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
					'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
					'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5'
				)
			),
			array(
				new ItemId( 'Q6' ),
				array(
					new PropertyId( 'P1' )
				),
				null
			),
			array(
				new ItemId( 'Q1' ),
				array(
					'P1'
				),
				null,
				InvalidArgumentException::class
			)
		);
	}

	/**
	 * @dataProvider crossCheckEntitiesByIdWithPropertiesDataProvider
	 */
	public function testCrossCheckEntitiesByIdWithProperties(
		array $entityIds,
		array $propertyIds,
		array $expectedResult = null,
		$expectedException = null
	) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$actualResult = $this->crossCheckInteractor->crossCheckEntitiesByIdWithProperties( $entityIds, $propertyIds );

		$this->runAssertions( $expectedResult, $actualResult );
	}

	/**
	 * Test cases for testCrossCheckEntitiesByIdWithProperties
	 * @return array
	 */
	public function crossCheckEntitiesByIdWithPropertiesDataProvider() {
		return array(
			array(
				array(
					new ItemId( 'Q1' ),
					new ItemId( 'Q2' )
				),
				array(
					new PropertyId( 'P1' )
				),
				array(
					'Q1' => array(
						'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
						'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7'
					),
					'Q2' => array(
						'Q2$0adcfe9e-cda1-4f74-bc98-433150e49b53',
						'Q2$07c00375-1be7-43a6-ac97-32770f2bb5ac'
					)
				)
			),
			array(
				array(
					new ItemId( 'Q1' ),
					new ItemId( 'Q2' )
				),
				array(
					new PropertyId( 'P1' ),
					new PropertyId( 'P2' )
				),
				array(
					'Q1' => array(
						'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
						'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
						'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5'
					),
					'Q2' => array(
						'Q2$0adcfe9e-cda1-4f74-bc98-433150e49b53',
						'Q2$07c00375-1be7-43a6-ac97-32770f2bb5ac'
					)
				)
			),
			array(
				array(
					'Q1',
					'Q2'
				),
				array(
					new PropertyId( 'P1' ),
					new PropertyId( 'P2' )
				),
				null,
				InvalidArgumentException::class
			),
			array(
				array(
					new ItemId( 'Q1' ),
					new ItemId( 'Q2' )
				),
				array(
					'P1',
					'P2'
				),
				null,
				InvalidArgumentException::class
			),
			array(
				array(
					'Q1',
					'Q2'
				),
				array(
					'P1',
					'P2'
				),
				null,
				InvalidArgumentException::class
			)
		);
	}

	/**
	 * @dataProvider crossCheckEntityWithPropertiesDataProvider
	 */
	public function testCrossCheckEntityWithProperties(
		StatementList $statements,
		array $propertyIds,
		array $expectedResult = null,
		$expectedException = null
	) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$actualResult = $this->crossCheckInteractor->crossCheckStatementsWithProperties( $statements, $propertyIds );

		$this->runAssertions( $expectedResult, $actualResult );
	}

	/**
	 * Test cases for testCrossCheckEntityWithProperties
	 * @return array
	 */
	public function crossCheckEntityWithPropertiesDataProvider() {
		return array(
			array(
				$this->items['Q1']->getStatements(),
				array(
					new PropertyId( 'P1' )
				),
				array(
					'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
					'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7'
				)
			),
			array(
				$this->items['Q1']->getStatements(),
				array(
					new PropertyId( 'P1' ),
					new PropertyId( 'P2' )
				),
				array(
					'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
					'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
					'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5'
				)
			),
			array(
				$this->items['Q1']->getStatements(),
				array(
					'P1'
				),
				null,
				InvalidArgumentException::class
			)
		);
	}

	/**
	 * @dataProvider crossCheckEntitiesWithPropertiesDataProvider
	 */
	public function testCrossCheckEntitiesWithProperties(
		array $entities,
		array $propertyIds,
		array $expectedResult = null,
		$expectedException = null
	) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$actualResult = $this->crossCheckInteractor->crossCheckEntitiesWithProperties( $entities, $propertyIds );

		$this->runAssertions( $expectedResult, $actualResult );
	}

	/**
	 * Test cases for testCrossCheckEntitiesWithProperties
	 * @return array
	 */
	public function crossCheckEntitiesWithPropertiesDataProvider() {
		return array(
			array(
				array(
					$this->items['Q1'],
					$this->items['Q2']
				),
				array(
					new PropertyId( 'P1' )
				),
				array(
					'Q1' => array(
						'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
						'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7'
					),
					'Q2' => array(
						'Q2$0adcfe9e-cda1-4f74-bc98-433150e49b53',
						'Q2$07c00375-1be7-43a6-ac97-32770f2bb5ac'
					)
				)
			),
			array(
				array(
					$this->items['Q1'],
					$this->items['Q2']
				),
				array(
					new PropertyId( 'P1' ),
					new PropertyId( 'P2' )
				),
				array(
					'Q1' => array(
						'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
						'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
						'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5'
					),
					'Q2' => array(
						'Q2$0adcfe9e-cda1-4f74-bc98-433150e49b53',
						'Q2$07c00375-1be7-43a6-ac97-32770f2bb5ac'
					)
				)
			),
			array(
				array(
					'Q1',
					'Q2'
				),
				array(
					new PropertyId( 'P1' ),
					new PropertyId( 'P2' )
				),
				null,
				InvalidArgumentException::class
			),
			array(
				array(
					$this->items['Q1'],
					$this->items['Q2']
				),
				array(
					'P1',
					'P2'
				),
				null,
				InvalidArgumentException::class
			),
			array(
				array(
					'Q1',
					'Q2'
				),
				array(
					'P1',
					'P2'
				),
				null,
				InvalidArgumentException::class
			)
		);
	}

	/**
	 * @dataProvider crossCheckClaimDataProvider
	 */
	public function testCrossCheckClaim(
		$guid,
		array $expectedResult = null,
		$expectedException = null
	) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$actualResult = $this->crossCheckInteractor->crossCheckStatementByGuid( $guid );

		$this->runAssertions( $expectedResult, $actualResult );
	}

	/**
	 * Test casses for testCrossCheckClaim
	 * @return array
	 */
	public function crossCheckClaimDataProvider() {
		return array(
			array(
				'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
				array(
					'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b'
				)
			),
			array(
				'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5',
				array(
					'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5'
				)
			),
			array(
				42,
				null,
				InvalidArgumentException::class
			)
		);
	}

	/**
	 * @dataProvider crossCheckClaimsDataProvider
	 */
	public function testCrossCheckClaims(
		array $guids,
		array $expectedResult = null,
		$expectedException = null
	) {
		if ( $expectedException ) {
			$this->setExpectedException( $expectedException );
		}

		$actualResult = $this->crossCheckInteractor->crossCheckStatementsByGuids( $guids );

		$this->runAssertions( $expectedResult, $actualResult );
	}

	/**
	 * Test casses for testCrossCheckClaims
	 * @return array
	 */
	public function crossCheckClaimsDataProvider() {
		return array(
			array(
				array(
					'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
					'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
					'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5'
				),
				array(
					'Q1' => array(
						'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
						'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
						'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5'
					)
				)
			),
			array(
				array(
					'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
					'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
					'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5',
					'Q2$0adcfe9e-cda1-4f74-bc98-433150e49b53',
					'Q2$07c00375-1be7-43a6-ac97-32770f2bb5ac'
				),
				array(
					'Q1' => array(
						'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
						'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
						'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5'
					),
					'Q2' => array(
						'Q2$0adcfe9e-cda1-4f74-bc98-433150e49b53',
						'Q2$07c00375-1be7-43a6-ac97-32770f2bb5ac'
					)
				)
			),
			array(
				array(
					'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
					'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
					'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5',
					'Q6$96ad2b99-a930-4632-98da-ec65abb7f2b0'
				),
				array(
					'Q1' => array(
						'Q1$c0f25a6f-9e33-41c8-be34-c86a730ff30b',
						'Q1$dd6dcfc9-55e2-4be6-b70c-d22f20f398b7',
						'Q1$01636a9a-97a5-478e-bf55-5d9a569c7ce5'
					),
					'Q6' => null
				)
			),
			array(
				array(
					'Q6$96ad2b99-a930-4632-98da-ec65abb7f2b0'
				),
				array(
					'Q6' => null
				)
			),
			array(
				array(
					42
				),
				null,
				InvalidArgumentException::class
			)
		);
	}

	private function runAssertions( array $expectedResult = null, $actualResult = null ) {
		if ( $expectedResult ) {
			$this->assertEquals( $expectedResult, $actualResult );
		} else {
			$this->assertNull( $actualResult );
		}
	}

}
