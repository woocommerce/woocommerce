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

		// Compile all .less files.
		less: {
			compile: {
				options: {
					// These paths are searched for @imports
					paths: ['<%= dirs.css %>/']
				},
				files: {
					'<%= dirs.css %>/activation.css': '<%= dirs.css %>/activation.less',
					'<%= dirs.css %>/admin.css': '<%= dirs.css %>/admin.less',
					'<%= dirs.css %>/chosen.css': '<%= dirs.css %>/chosen.less',
					'<%= dirs.css %>/dashboard.css': '<%= dirs.css %>/dashboard.less',
					'<%= dirs.css %>/menu.css': '<%= dirs.css %>/menu.less',
					'<%= dirs.css %>/prettyPhoto.css': '<%= dirs.css %>/prettyPhoto.less',
					'<%= dirs.css %>/woocommerce-layout.css': '<%= dirs.css %>/woocommerce-layout.less',
					'<%= dirs.css %>/woocommerce-smallscreen.css': '<%= dirs.css %>/woocommerce-smallscreen.less',
					'<%= dirs.css %>/woocommerce.css': '<%= dirs.css %>/woocommerce.less'
				}
			}
		},

		// Minify all .css files.
		cssmin: {
			minify: {
				expand: true,
				cwd: '<%= dirs.css %>/',
				src: ['*.css'],
				dest: '<%= dirs.css %>/',
				ext: '.css'
			}
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
