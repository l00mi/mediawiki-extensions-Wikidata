<?php

namespace WikibaseQuality\ExternalValidation\DumpMetaInformation;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * Interface DumpMetaInformationLookup
 * @package WikibaseQuality\ExternalValidation\DumpMetaInformation
 * @author BP2014N1
 * @license GNU GPL v2+
 */
interface DumpMetaInformationLookup {

	/**
	 * Gets DumpMetaInformation for specific dump ids from database
	 * Returns array in the form 'dumpId' => DumpMetaInformation
	 *
	 * @param string[] $dumpIds
	 * @return DumpMetaInformation[]
	 * @throws \InvalidArgumentException
	 */
	public function getWithIds( array $dumpIds );

	/**
	 * Gets DumpMetaInformation for specific identifier properties from database
	 * Returns array in the form 'dumpId' => DumpMetaInformation
	 *
	 * @param PropertyId[] $identifierPropertyIds
	 *
	 * @return DumpMetaInformation[]
	 */
	public function getWithIdentifierProperties( array $identifierPropertyIds );

	/**
	 * Gets all DumpMetaInformation from database
	 * Returns array in the form 'dumpId' => DumpMetaInformation
	 *
	 * @return DumpMetaInformation[]
	 */
	public function getAll();

}
