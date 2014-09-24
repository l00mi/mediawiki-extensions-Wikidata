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
		},
		updatecomposer: {
			main: {
				src: 'composer.json',
				branchName: grunt.option( "branchName" )
			}
		}
	} );

	grunt.loadNpmTasks( 'grunt-exec' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadTasks( 'build/tasks' );

	grunt.registerTask( 'uninstall', [ 'clean:build' ] );
	grunt.registerTask( 'install', [ 'clean:build', 'exec:install' ] );
	grunt.registerTask( 'branch', [ 'uninstall', 'updatecomposer', 'exec:install' ] );

};
