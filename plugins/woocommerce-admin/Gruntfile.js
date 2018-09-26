/* eslint-disable */
module.exports = function( grunt ) {

	'use strict';

	// Project configuration
	grunt.initConfig( {
		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					exclude: [ '\.git/*', 'bin/*', 'node_modules/*', 'tests/*' ],
					mainFile: 'wc-admin.php',
					potFilename: 'wc-admin.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true
					},
					type: 'wp-plugin',
					updateTimestamp: true
				}
			}
		},
	} );

	// Load NPM tasks to be used here.
	grunt.loadNpmTasks( 'grunt-wp-i18n' );

	grunt.util.linefeed = '\n';

};
