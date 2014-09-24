'use strict';

module.exports = function ( grunt ) {

	grunt.initConfig( {
		clean: {
			build: {
				src: [
					'composer.lock',
					'WikibaseClient.settings.php',
					'WikibaseRepo.settings.php',
					'extensions',
					'vendor'
				]
			}
		},
		exec: {
			install: {
				cmd: 'composer install --ansi --prefer-dist -o'
			}
		}
	} );

	grunt.loadNpmTasks( 'grunt-exec' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );

	grunt.registerTask( 'uninstall', [ 'clean:build' ] );
	grunt.registerTask( 'install', [ 'clean:build', 'exec:install' ] );

};
