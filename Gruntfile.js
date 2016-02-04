/*jshint node:true */
module.exports = function ( grunt ) {

	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-exec' );
	grunt.loadNpmTasks( 'grunt-jsonlint' );

	grunt.initConfig( {
		jshint: {
			options: {
				jshintrc: true
			},
			all: [
				'*.js',
				'build/**/*.js'
			]
		},
		jsonlint: {
			all: [
			    '*.json'
			]
		},
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

	grunt.loadTasks( 'build/tasks' );

	grunt.registerTask( 'test', [ 'jshint', 'jsonlint' ] );
	grunt.registerTask( 'uninstall', [ 'clean:build' ] );
	grunt.registerTask( 'install', [ 'clean:build', 'exec:install' ] );
	grunt.registerTask( 'branch', [ 'uninstall', 'updatecomposer', 'exec:install' ] );

};
