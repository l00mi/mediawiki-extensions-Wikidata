<?php

namespace DataValues;

/**
 * Class for performing basic arithmetic and other transformations
 * on DecimalValues.
 *
 * This uses the bcmath library if available. Otherwise, it falls back on
 * using floating point operations.
 *
 * @note: this is not a genuine decimal arithmetics implementation,
 * and should not be used for financial computations, physical simulations, etc.
 *
 * @see DecimalValue
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DecimalMath {

	/**
	 * Whether to use the bcmath library.
	 *
	 * @var bool
	 */
	protected $useBC;

	/**
	 * @param bool|null $useBC Whether to use the bcmath library. If null,
	 *        bcmath will automatically be used if available.
	 */
	public function __construct( $useBC = null ) {
		if ( $useBC === null ) {
			$useBC = function_exists( 'bcscale' );
		}

		$this->useBC = $useBC;
	}

	/**
	 * @param int|float|string $number
	 *
	 * @return DecimalValue
	 */
	private function makeDecimalValue( $number ) {

		if ( is_string( $number ) && $number !== '' ) {
			if ( $number[0] !== '-' && $number[0] !== '+' ) {
				$number = '+' . $number;
			}
		}

		return new DecimalValue( $number );
	}

	/**
	 * Whether this is using the bcmath library.
	 *
	 * @return bool
	 */
	public function getUseBC() {
		return $this->useBC;
	}

	/**
	 * Returns the product of the two values.
	 *
	 * @param DecimalValue $a
	 * @param DecimalValue $b
	 *
	 * @return DecimalValue
	 */
	public function product( DecimalValue $a, DecimalValue $b ) {
		if ( $this->useBC ) {
			$scale = strlen( $a->getFractionalPart() ) + strlen( $b->getFractionalPart() );
			$product = bcmul( $a->getValue(), $b->getValue(), $scale );
		} else {
			$product = $a->getValueFloat() * $b->getValueFloat();
		}

		return $this->makeDecimalValue( $product );
	}

	/**
	 * Returns the sum of the two values.
	 *
	 * @param DecimalValue $a
	 * @param DecimalValue $b
	 *
	 * @return DecimalValue
	 */
	public function sum( DecimalValue $a, DecimalValue $b ) {
		if ( $this->useBC ) {
			$scale = max( strlen( $a->getFractionalPart() ), strlen( $b->getFractionalPart() ) );
			$sum = bcadd( $a->getValue(), $b->getValue(), $scale );
		} else {
			$sum = $a->getValueFloat() + $b->getValueFloat();
		}

		return $this->makeDecimalValue( $sum );
	}

	/**
	 * Returns the minimum of the two values
	 *
	 * @param DecimalValue $a
	 * @param DecimalValue $b
	 *
	 * @return DecimalValue
	 */
	public function min( DecimalValue $a, DecimalValue $b ) {

		if ( $this->useBC ) {
			$scale = max( strlen( $a->getFractionalPart() ), strlen( $b->getFractionalPart() ) );
			$comp = bccomp( $a->getValue(), $b->getValue(), $scale );
			$min = $comp > 0 ? $b : $a;
		} else {
			$min = min( $a->getValueFloat(), $b->getValueFloat() );
			$min = $this->makeDecimalValue( $min );
		}

		return $min;
	}

	/**
	 * Returns the maximum of the two values
	 *
	 * @param DecimalValue $a
	 * @param DecimalValue $b
	 *
	 * @return DecimalValue
	 */
	public function max( DecimalValue $a, DecimalValue $b ) {

		if ( $this->useBC ) {
			$scale = max( strlen( $a->getFractionalPart() ), strlen( $b->getFractionalPart() ) );
			$comp = bccomp( $a->getValue(), $b->getValue(), $scale );
			$max = $comp > 0 ? $a : $b;
		} else {
			$max = max( $a->getValueFloat(), $b->getValueFloat() );
			$max = $this->makeDecimalValue( $max );
		}

		return $max;
	}

	/**
	 * Returns the given value, with any insignificant digits removed or zeroed.
	 *
	 * Rounding is applied  using the "round half away from zero" rule (that is, +0.5 is
	 * rounded to +1 and -0.5 is rounded to -1).
	 *
	 * @since 0.1
	 *
	 * @param DecimalValue $decimal
	 * @param int $significantDigits The number of digits to retain, counting the decimal point,
	 *        but not counting the leading sign.
	 *
	 * @throws \InvalidArgumentException
	 * @return DecimalValue
	 */
	public function roundToDigit( DecimalValue $decimal, $significantDigits ) {
		$value = $decimal->getValue();
		$rounded = $this->roundDigits( $value, $significantDigits );
		return new DecimalValue( $rounded );
	}

	/**
	 * Returns the given value, with any insignificant digits removed or zeroed.
	 *
	 * Rounding is applied  using the "round half away from zero" rule (that is, +0.5 is
	 * rounded to +1 and -0.5 is rounded to -1).
	 *
	 * @since 0.1
	 *
	 * @param DecimalValue $decimal
	 * @param int $significantExponent 	 The exponent of the last significant digit,
	 *        e.g. -1 for "keep the first digit after the decimal point", or 2 for
	 *        "zero the last two digits before the decimal point".
	 *
	 * @throws \InvalidArgumentException
	 * @return DecimalValue
	 */
	public function roundToExponent( DecimalValue $decimal, $significantExponent ) {
		//NOTE: the number of digits to keep (without the leading sign)
		//      is the same as the exponent's offset (with the leaqding sign).
		$digits = $this->getPositionForExponent( $significantExponent, $decimal );
		return $this->roundToDigit( $decimal, $digits );
	}

	/**
	 * Returns the (zero based) position for the given exponent in
	 * the given decimal string, counting the decimal point and the leading sign.
	 *
	 * @example: the position of exponent 0 in "+10.03" is 2.
	 * @example: the position of exponent 1 in "+210.03" is 2.
	 * @example: the position of exponent -2 in "+1.037" is 4.
	 *
	 * @param int $exponent
	 * @param string $decimal
	 */
	public function getPositionForExponent( $exponent, DecimalValue $decimal ) {
		$decimal = $decimal->getValue();

		$pointPos = strpos( $decimal, '.' );
		if ( $pointPos === false ) {
			$pointPos = strlen( $decimal );
		}

		// account for leading sign
		$pointPos--;

		if ( $exponent < 0 ) {
			// account for decimal point
			$position = $pointPos +1 - $exponent;
		} else {
			// make sure we don't remove more digits than are there
			$position = max( 0, $pointPos - $exponent );
		}

		return $position;
	}

	/**
	 * Returns the given value, with any insignificant digits removed or zeroed.
	 *
	 * Rounding is applied using the "round half away from zero" rule (that is, +0.5 is
	 * rounded to +1 and -0.5 is rounded to -1).
	 *
	 * @see round()
	 *
	 * @param string $value
	 * @param int $significantDigits
	 *
	 * @throws \InvalidArgumentException if $significantDigits is smaller than 0
	 * @return string
	 */
	protected function roundDigits( $value, $significantDigits ) {
		if ( !is_int( $significantDigits ) ) {
			throw new \InvalidArgumentException( '$significantDigits must be an integer' );
		}

		// keeping no digits results in zero.
		if ( $significantDigits === 0 ) {
			return '+0';
		}

		if ( $significantDigits < 0 ) {
			throw new \InvalidArgumentException( '$significantDigits must be larger than zero.' );
		}

		// whether the last character is already part of the integer part of the decimal value
		$inIntPart = ( strpos( $value, '.' ) === false );

		$rounded = '';

		// Iterate over characters from right to left and build the result back to front.
		for ( $i = strlen( $value ) -1; $i > 0 && $i > $significantDigits; $i-- ) {

			list( $value, $i, $inIntPart, $next ) = $this->roundNextDigit( $value, $i, $inIntPart );

			$rounded = $next . $rounded;
		}

		// just keep the remainder of the value as is (this includes the sign)
		$rounded = substr( $value, 0, $i +1 ) . $rounded;

		if ( strlen( $rounded ) < $significantDigits + 1 ) {
			if ( $inIntPart ) {
				$rounded .= '.';
			}

			$rounded = str_pad( $rounded, $significantDigits+1, '0', STR_PAD_RIGHT );
		}

		// strip trailing decimal point
		$rounded = rtrim( $rounded, '.' );

		return $rounded;
	}

	/**
	 * Extracts the next character to add to the result of a rounding run:
	 * $value[$] will be examined and processed in order to determine the next
	 * character to prepend to the result (returned in the $nextCharacter field).
	 *
	 * Updated values for the parameters are returned as well as the next
	 * character.
	 *
	 * @param string $value
	 * @param int $i
	 * @param bool $inIntPart
	 *
	 * @return array ( $value, $i, $inIntPart, $nextCharacter )
	 */
	private function roundNextDigit( $value, $i, $inIntPart ) {
		// next digit
		$ch = $value[$i];

		if ( $ch === '.' ) {
			// just transition from the fractional to the integer part
			$inIntPart = true;
			$nextCharacter = '.';
		} else {
			if ( $inIntPart ) {
				// in the integer part, zero out insignificant digits
				$nextCharacter = '0';
			} else {
				// in the fractional part, strip insignificant digits
				$nextCharacter = '';
			}

			if ( ord( $ch ) >= ord( '5' ) ) {
				// when stripping a character >= 5, bump up the next digit to the left.
				list( $value, $i, $inIntPart ) = $this->bumpDigitsForRounding( $value, $i, $inIntPart );
			}
		}

		return array( $value, $i, $inIntPart, $nextCharacter );
	}

	/**
	 * Bumps the last digit of a value that is being processed for rounding while taking
	 * care of edge cases and updating the state of the rounding process.
	 *
	 * - $value is truncated to $i digits, so we can safely increment (bump) the last digit.
	 * - if the last character of $value is '.', it's trimmed (and $inIntPart is set to true)
	 *   to handle the transition from the fractional to the integer part of $value.
	 * - the last digit of $value is bumped using bumpDigits() - this is where the magic happens.
	 * - $i is set to strln( $value ) to make the index consistent in case a trailing decimal
	 *   point got removed.
	 *
	 * Updated values for the parameters are returned.
	 * Note: when returning, $i is always one greater than the greatest valid index in $value.
	 *
	 * @param string $value
	 * @param int $i
	 * @param bool $inIntPart
	 *
	 * @return array ( $value, $i, $inIntPart, $next )
	 */
	private function bumpDigitsForRounding( $value, $i, $inIntPart ) {
		$remaining = substr( $value, 0, $i );

		// If there's a '.' at the end, strip it and note that we are in the
		// integer part of $value now.
		if ( $remaining[ strlen( $remaining ) -1 ] === '.' ) {
			$remaining = rtrim( $remaining, '.' );
			$inIntPart = true;
		}

		// Rounding may add digits, adjust $i for that.
		$value = $this->bumpDigits( $remaining );
		$i = strlen( $value );

		return array( $value, $i, $inIntPart );
	}

	/**
	 * Increment the least significant digit by one if it is less than 9, and
	 * set it to zero and continue to the next more significant digit if it is 9.
	 * Exception: bump( 0 ) == 1;
	 *
	 * E.g.: bump( 0.2 ) == 0.3, bump( -0.09 ) == -0.10, bump( 9.99 ) == 10.00
	 *
	 * This is the inverse of @see slump()
	 *
	 * @since 0.1
	 *
	 * @param DecimalValue $decimal
	 *
	 * @return DecimalValue
	 */
	public function bump( DecimalValue $decimal ) {
		$value = $decimal->getValue();
		$bumped = $this->bumpDigits( $value );
		return new DecimalValue( $bumped );
	}

	/**
	 * Increment the least significant digit by one if it is less than 9, and
	 * set it to zero and continue to the next more significant digit if it is 9.
	 *
	 * @see bump()
	 *
	 * @param string $value
	 * @return string
	 */
	protected function bumpDigits( $value ) {
		if ( $value === '+0' ) {
			return '+1';
		}

		$bumped = '';

		for ( $i = strlen( $value ) -1; $i >= 0; $i-- ) {
			$ch = $value[$i];

			if ( $ch === '.' ) {
				$bumped = '.' . $bumped;
				continue;
			} elseif ( $ch === '9' ) {
				$bumped = '0' . $bumped;
				continue;
			} elseif ( $ch === '+' || $ch === '-' ) {
				$bumped = $ch . '1' . $bumped;
				break;
			} else {
				$bumped =  chr( ord( $ch ) + 1 ) . $bumped;
				break;
			}
		}

		$bumped = substr( $value, 0, $i ) . $bumped;
		return $bumped;
	}

	/**
	 * Decrement the least significant digit by one if it is more than 0, and
	 * set it to 9 and continue to the next more significant digit if it is 0.
	 * Exception: slump( 0 ) == -1;
	 *
	 * E.g.: slump( 0.2 ) == 0.1, slump( -0.10 ) == -0.01, slump( 0.0 ) == -1.0
	 *
	 * This is the inverse of @see bump()
	 *
	 * @since 0.1
	 *
	 * @param DecimalValue $decimal
	 *
	 * @return DecimalValue
	 */
	public function slump( DecimalValue $decimal ) {
		$value = $decimal->getValue();
		$slumped = $this->slumpDigits( $value );
		return new DecimalValue( $slumped );
	}

	/**
	 * Decrement the least significant digit by one if it is more than 0, and
	 * set it to 9 and continue to the next more significant digit if it is 0.
	 *
	 * @see slump()
	 *
	 * @param string $value
	 * @return string
	 */
	protected function slumpDigits( $value ) {
		if ( $value === '+0' ) {
			return '-1';
		}

		// a "precise zero" will become negative
		if ( preg_match( '/^\+0\.(0*)0$/', $value, $m ) ) {
			return '-0.' . $m[1] . '1';
		}

		$slumped = '';

		for ( $i = strlen( $value ) -1; $i >= 0; $i-- ) {
			$ch = substr( $value, $i, 1 );

			if ( $ch === '.' ) {
				$slumped = '.' . $slumped;
				continue;
			} elseif ( $ch === '0' ) {
				$slumped = '9' . $slumped;
				continue;
			} elseif ( $ch === '+' || $ch === '-' ) {
				$slumped = '0';
				break;
			} else {
				$slumped =  chr( ord( $ch ) - 1 ) . $slumped;
				break;
			}
		}

		// preserve prefix
		$slumped = substr( $value, 0, $i ) . $slumped;

		$slumped = $this->stripLeadingZeros( $slumped );

		if ( $slumped === '-0' ) {
			$slumped = '+0';
		}

		return $slumped;
	}

	/**
	 * @param string $digits
	 *
	 * @return string
	 */
	protected function stripLeadingZeros( $digits ) {
		$digits = preg_replace( '/^([-+])(0+)([0-9]+(\.|$))/', '\1\3', $digits );
		return $digits;
	}

	/**
	 * Shift the decimal point according to the given exponent.
	 *
	 * @param DecimalValue $decimal
	 * @param int $exponent The exponent to apply (digits to shift by). A Positive exponent
	 * shifts the decimal point to the right, a negative exponent shifts to the left.
	 *
	 * @throws \InvalidArgumentException
	 * @return DecimalValue
	 */
	public function shift( DecimalValue $decimal, $exponent ) {
		if ( !is_int( $exponent ) ) {
			throw new \InvalidArgumentException( '$exponent must be an integer' );
		}

		if ( $exponent == 0 ) {
			return $decimal;
		}

		$sign = $decimal->getSign();
		$intPart = $decimal->getIntegerPart();
		$fractPart = $decimal->getFractionalPart();

		if ( $exponent < 0 ) {
			$intPart = $this->shiftLeft( $intPart, $exponent );
		} else {
			$fractPart = $this->shiftRight( $fractPart, $exponent );
		}

		$digits = $sign . $intPart . $fractPart;
		$digits = $this->stripLeadingZeros( $digits );

		return new DecimalValue( $digits );
	}

	/**
	 * @param string $intPart
	 * @param int $exponent must be negative
	 *
	 * @return string
	 */
	private function shiftLeft( $intPart, $exponent ) {
		//note: $exponent is negative!
		if ( -$exponent < strlen( $intPart ) ) {
			$intPart = substr( $intPart, 0, $exponent ) . '.' . substr( $intPart, $exponent );
		} else {
			$intPart = '0.' . str_pad( $intPart, -$exponent, '0', STR_PAD_LEFT );
		}

		return $intPart;
	}

	/**
	 * @param string $fractPart
	 * @param int $exponent must be positive
	 *
	 * @return string
	 */
	private function shiftRight( $fractPart, $exponent ) {
		//note: $exponent is positive.
		if ( $exponent < strlen( $fractPart ) ) {
			$fractPart = substr( $fractPart, 0, $exponent ) . '.' . substr( $fractPart, $exponent );
		} else {
			$fractPart = str_pad( $fractPart, $exponent, '0', STR_PAD_RIGHT );
		}

		return $fractPart;
	}
}