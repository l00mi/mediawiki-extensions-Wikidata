<?php

namespace WikibaseQuality\ExternalValidation\DumpMetaInformation;


/**
 * Interface DumpMetaInformationStore
 * @package WikibaseQuality\ExternalValidation\DumpMetaInformation
 * @author BP2014N1
 * @license GNU GPL v2+
 */
interface DumpMetaInformationStore {

	/**
	 * Inserts or updates given dump meta information to database
	 *
	 * @param DumpMetaInformation $dumpMetaInformation
	 */
	public function save( DumpMetaInformation $dumpMetaInformation );
}