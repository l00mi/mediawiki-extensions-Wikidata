<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Comparer;

use DataValues\DataValue;
use DataValues\TimeValue;
use DateInterval;
use InvalidArgumentException;
use MWTimestamp;
use TimestampException;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Repo\Parsers\TimeParserFactory;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;

/**
 * @package WikibaseQuality\ExternalValidation\CrossCheck\Comparer
 * @author BP2014N1
 * @license GNU GPL v2+
 */
class TimeValueComparer implements DataValueComparer {

	/**
	 * @see DataValueComparer::compare
	 *
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	public function compare( DataValue $value, DataValue $comparativeValue ) {
		if ( !$this->canCompare( $value, $comparativeValue ) ) {
			throw new InvalidArgumentException( 'Given values can not be compared using this comparer.' );
		}

		/**
		 * @var TimeValue $value
		 * @var TimeValue $comparativeValue
		 */

		$result = ComparisonResult::STATUS_MISMATCH;

		try {
			// FIXME: MWTimestamp does not support years with more than 4 digits!
			$localTimestamp = new MWTimestamp( substr( $value->getTime(), 1 ) );
			$externalTimestamp = new MWTimestamp( substr( $comparativeValue->getTime(), 1 ) );
			$diff = $localTimestamp->diff( $externalTimestamp, true );

			if ( $value->getPrecision() === $comparativeValue->getPrecision()
				&& $this->resultOfDiffWithPrecision( $diff, $value->getPrecision() )
			) {
				$result = ComparisonResult::STATUS_MATCH;
			} elseif (
				$this->resultOfDiffWithPrecision(
					$diff,
					min( $value->getPrecision(), $comparativeValue->getPrecision() )
				)
			) {
				$result = ComparisonResult::STATUS_PARTIAL_MATCH;
			}
		} catch ( TimestampException $ex ) {
		}

		return $result;
	}

	/**
	 * Returns boolean if diff is equal depending on the given precision
	 *
	 * @param DateInterval $diff
	 * @param int $precision
	 *
	 * @return bool
	 */
	private function resultOfDiffWithPrecision( DateInterval $diff, $precision ) {
		$result = true;

		switch ( $precision ) {
			case TimeValue::PRECISION_SECOND:
				$result = $result && $diff->s === 0;
				// Fall through with no break/return. This is critical for this algorithm.
			case TimeValue::PRECISION_MINUTE:
				$result = $result && $diff->i === 0;
			case TimeValue::PRECISION_HOUR:
				$result = $result && $diff->h === 0;
			case TimeValue::PRECISION_DAY:
				$result = $result && $diff->d === 0;
			case TimeValue::PRECISION_MONTH:
				$result = $result && $diff->m === 0;
			case TimeValue::PRECISION_YEAR:
				return $result && $diff->y === 0;
			case TimeValue::PRECISION_YEAR10:
				return $diff->y < 10;
			case TimeValue::PRECISION_YEAR100:
				return $diff->y < 100;
			case TimeValue::PRECISION_YEAR1K:
				return $diff->y < 1000;
			case TimeValue::PRECISION_YEAR10K:
				return $diff->y < 10000;
			case TimeValue::PRECISION_YEAR100K:
				return $diff->y < 100000;
			case TimeValue::PRECISION_YEAR1M:
				return $diff->y < 1000000;
			case TimeValue::PRECISION_YEAR10M:
				return $diff->y < 10000000;
			case TimeValue::PRECISION_YEAR100M:
				return $diff->y < 100000000;
			case TimeValue::PRECISION_YEAR1G:
				return $diff->y < 1000000000;
			default:
				return false;
		}
	}

	/**
	 * @see DataValueComparerBase::getExternalValueParser
	 *
	 * @param DumpMetaInformation $dumpMetaInformation
	 *
	 * @return ValueParser
	 */
	protected function getExternalValueParser( DumpMetaInformation $dumpMetaInformation ) {
		$parserOptions = new ParserOptions();
		$parserOptions->setOption( ValueParser::OPT_LANG, $dumpMetaInformation->getLanguageCode() );
		$timeParserFactory = new TimeParserFactory( $parserOptions );

		return $timeParserFactory->getTimeParser();
	}

	/**
	 * @see DataValueComparer::canCompare
	 *
	 * @param DataValue $value
	 * @param DataValue $comparativeValue
	 *
	 * @return bool
	 */
	public function canCompare( DataValue $value, DataValue $comparativeValue ) {
		return $value instanceof TimeValue && $comparativeValue instanceof TimeValue;
	}

}
