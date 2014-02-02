/* jshint node:true */
module.exports = function( grunt ){
	'use strict';

	grunt.initConfig({
		// setting folder templates
		dirs: {
			css: 'assets/css',
			fonts: 'assets/fonts',
			images: 'assets/images',
			js: 'assets/js'
		},

		// Compile specified less files
		less: {
			compile: {
				options: {
					// These paths are searched for @imports
					paths: ['assets/css']
				},
				files: {
					'<%= dirs.css %>/woocommerce.css': '<%= dirs.css %>/woocommerce.less'
				}
			}
		},

		cssmin: {
			'<%= dirs.css %>/woocommerce.css': ['<%= dirs.css %>/woocommerce.css']
		},

		shell: {
			generatemos: {
				command: [
					'cd i18n/languages',
					'for i in *.po; do msgfmt $i -o ${i%%.*}.mo; done',
					'ls'
				].join( '&&' )
			}
		},

	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-shell' );
	grunt.loadNpmTasks( 'grunt-contrib-less' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );

	// Register tasks
	grunt.registerTask( 'default', []);
	grunt.registerTask( 'dev', ['less:compile', 'cssmin', 'shell:generatemos' ] );

};
