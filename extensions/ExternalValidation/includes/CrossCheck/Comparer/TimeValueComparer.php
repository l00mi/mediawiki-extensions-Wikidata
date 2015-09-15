<?php

namespace WikibaseQuality\ExternalValidation\CrossCheck\Comparer;

use InvalidArgumentException;
use DateInterval;
use DataValues\DataValue;
use DataValues\TimeValue;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Lib\Parsers\TimeParserFactory;
use WikibaseQuality\ExternalValidation\CrossCheck\Result\ComparisonResult;
use WikibaseQuality\ExternalValidation\DumpMetaInformation\DumpMetaInformation;
use MWTimestamp;
use TimestampException;

/**
 * Class TimeValueComparer
 *
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
	 * @return string
	 */
	public function compare( DataValue $value, DataValue $comparativeValue ) {
		if( !$this->canCompare( $value, $comparativeValue ) ) {
			throw new InvalidArgumentException( 'Given values can not be compared using this comparer.' );
		}

		$result = ComparisonResult::STATUS_MISMATCH;

		try {
			$localTimestamp = new MWTimestamp(substr($value->getTime(), 1));
			$externalTimestamp = new MWTimestamp(substr($comparativeValue->getTime(), 1));
			$diff = $localTimestamp->diff( $externalTimestamp, true );

			if ( $value->getPrecision() === $comparativeValue->getPrecision()
					&& $this->resultOfDiffWithPrecision( $diff, $value->getPrecision() ) ) {
				$result = ComparisonResult::STATUS_MATCH;
			} elseif (
				$this->resultOfDiffWithPrecision(
					$diff,
					min( $value->getPrecision(), $comparativeValue->getPrecision() )
				)
			) {
				$result = ComparisonResult::STATUS_PARTIAL_MATCH;
			}
		} catch ( TimestampException $e ){ }

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
	private function resultOfDiffWithPrecision( $diff, $precision ) {
		$result = true;
		switch ( $precision ) {
			case TimeValue::PRECISION_MINUTE:
				$result = $result && $diff->i === 0;
			case TimeValue::PRECISION_HOUR:
				$result = $result && $diff->h === 0;
			case TimeValue::PRECISION_DAY:
				$result = $result && $diff->d === 0;
			case TimeValue::PRECISION_MONTH:
				$result = $result && $diff->m === 0;
			case TimeValue::PRECISION_YEAR:
				$result = $result && $diff->y === 0;
			case TimeValue::PRECISION_10a:
				$result = $result && $diff->y < 10;
			case TimeValue::PRECISION_100a:
				$result = $result && $diff->y < 100;
			case TimeValue::PRECISION_ka:
				$result = $result && $diff->y < 1000;
			case TimeValue::PRECISION_10ka:
				$result = $result && $diff->y < 10000;
			case TimeValue::PRECISION_100ka:
				$result = $result && $diff->y < 100000;
			case TimeValue::PRECISION_Ma:
				$result = $result && $diff->y < 1000000;
			case TimeValue::PRECISION_10Ma:
				$result = $result && $diff->y < 10000000;
			case TimeValue::PRECISION_100Ma:
				$result = $result && $diff->y < 100000000;
			case TimeValue::PRECISION_Ga:
				$result = $result && $diff->y < 1000000000;
				break;
			default:
				$result = false;
		}

		return $result;
	}

	/**
	 * @see DataValueComparerBase::getExternalValueParser
	 *
	 * @param DumpMetaInformation $dumpMetaInformation
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
	 * @return bool
	 */
	public function canCompare( DataValue $value, DataValue $comparativeValue ) {
		return $value instanceof TimeValue && $comparativeValue instanceof TimeValue;
	}
}
