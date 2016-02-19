<?php

namespace Wikibase\DataModel\Services\EntityId;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 1.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class PlainEntityIdFormatter implements EntityIdFormatter {

	/**
	 * @see EntityIdFormatter::formatEntityId
	 *
	 * @param EntityId $entityId
	 *
	 * @return string Plain text
	 */
	public function formatEntityId( EntityId $entityId ) {
		return $entityId->getSerialization();
	}

}
