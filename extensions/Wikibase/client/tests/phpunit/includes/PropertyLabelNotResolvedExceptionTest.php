<?php

namespace Wikibase\Client\Tests;

use Exception;
use PHPUnit_Framework_TestCase;
use Wikibase\Client\PropertyLabelNotResolvedException;

/**
 * @covers Wikibase\Client\PropertyLabelNotResolvedException
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class PropertyLabelNotResolvedExceptionTest extends PHPUnit_Framework_TestCase {

	public function testWithDefaultParameters() {
		$ex = new PropertyLabelNotResolvedException( '<LABEL>', '<LANGUAGECODE>' );
		$message = $ex->getMessage();
		$this->assertContains( '<LABEL>', $message );
		$this->assertContains( '<LANGUAGECODE>', $message );
		$this->assertSame( 0, $ex->getCode() );
		$this->assertNull( $ex->getPrevious() );
	}

	public function testWithCustomParameters() {
		$previous = new Exception();
		$ex = new PropertyLabelNotResolvedException( '', '', '<MESSAGE>', $previous );
		$message = $ex->getMessage();
		$this->assertSame( '<MESSAGE>', $message );
		$this->assertSame( 0, $ex->getCode() );
		$this->assertSame( $previous, $ex->getPrevious() );
	}

}
