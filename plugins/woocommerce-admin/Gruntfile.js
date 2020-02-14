/* eslint-disable */
module.exports = function( grunt ) {
	'use strict';

	// Project configuration
	grunt.initConfig( {
		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					exclude: [ '.git/*', 'bin/*', 'node_modules/*', 'tests/*' ],
					mainFile: 'woocommerce-admin.php',
					potFilename: 'woocommerce-admin.pot',
					potHeaders: {
						poedit: true,
						'x-poedit-keywordslist': true,
					},
					type: 'wp-plugin',
					updateTimestamp: true,
				},
			},
		},

		checktextdomain: {
			options: {
				text_domain: 'woocommerce-admin',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d',
				],
			},
			files: {
				src: [
					'**/*.php', // Include all files/
					'!node_modules/**', // Exclude node_modules/
					'!tests/**', // Exclude tests/
					'!vendor/**', // Exclude vendor/
					'!tmp/**', // Exclude tmp/
				],
				expand: true,
			},
		},
	} );

	// Load NPM tasks to be used here.
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-checktextdomain' );

	grunt.util.linefeed = '\n';
};
