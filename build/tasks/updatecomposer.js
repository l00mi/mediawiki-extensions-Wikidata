/*jshint node:true */
var path = require( 'path' );

module.exports = function ( grunt ) {

	function autoloadSuffix( branch ) {
		var suffix = "wikidata_" + branch.substring( branch.lastIndexOf( "/" ) + 1 );
		suffix = suffix.replace( /[\.\-]/g, '_' );

		return suffix;
	}

	grunt.registerMultiTask( 'updatecomposer', 'Make a deployment branch', function () {
		var composerPath = path.join( __dirname, '../..', 'composer.json' ),
			composer = grunt.file.readJSON( composerPath ),
			branch = this.data.branchName;

		composer.require["wikibase/wikibase"] = 'dev-' + branch;

		// Don't include in deployment builds while security review is pending.
		delete composer.require["wikibase/external-validation"];

		delete composer.config["github-oauth"];
		composer.config["autoloader-suffix"] = autoloadSuffix( branch );

		grunt.file.write( composerPath, JSON.stringify( composer, null, '    ' ) );
		grunt.log.ok( 'Updated composer file' );
	} );

};
