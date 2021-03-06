/**
 * @licence GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
/* jshint nonew: false */
( function( $, ExpertExtender, sinon, QUnit ) {
	'use strict';

	QUnit.module( 'jquery.valueview.ExpertExtender' );

	QUnit.test( 'Constructor', function( assert ) {
		assert.expect( 2 );
		var expertExtender = new ExpertExtender( $( '<input/>' ), [] );

		assert.ok(
			expertExtender instanceof ExpertExtender,
			'Instantiated ExpertExtender.'
		);

		assert.notDeepEqual( expertExtender, ExpertExtender.prototype );
	} );

	QUnit.test( 'destroy cleans up properties', function( assert ) {
		assert.expect( 1 );
		var expertExtender = new ExpertExtender( $( '<input/>' ), [] );

		expertExtender.destroy();

		assert.deepEqual( expertExtender, ExpertExtender.prototype );
	} );

	QUnit.test( 'destroy calls extensions', function( assert ) {
		assert.expect( 1 );
		var destroy = sinon.spy(),
			expertExtender = new ExpertExtender( $( '<input/>' ), [ {
				destroy: destroy
			} ] );

		expertExtender.destroy();

		sinon.assert.calledOnce( destroy );
	} );

	QUnit.asyncTest( 'init calls extensions', function( assert ) {
		assert.expect( 3 );
		var $input = $( '<input/>' ).appendTo( 'body' ),
			init = sinon.spy(),
			onInitialShow = sinon.spy(),
			draw = sinon.spy(),
			expertExtender = new ExpertExtender( $input, [ {
				init: init,
				onInitialShow: onInitialShow,
				draw: draw
			} ] );

		$input.focus();
		expertExtender.init();
		// inputextender immediately extends if $input has focus
		// If, after focussing, $input does not have focus, we are running in phantomjs
		// or an unfocused firefox window. Force showing the extension, then.
		if ( !$input.is( ':focus' ) ) {
			expertExtender._inputextender.showExtension();
		}

		window.setTimeout( function() {
			sinon.assert.calledOnce( init );
			sinon.assert.calledOnce( onInitialShow );
			sinon.assert.calledOnce( draw );

			$input.remove();

			QUnit.start();
		}, 0 );
	} );

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	sinon,
	QUnit
);
