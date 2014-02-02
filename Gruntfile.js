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

		// Minify .js files.
		uglify: {
			options: {
				preserveComments: 'some'
			},
			admin: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/admin/',
					src: [
						'*.js',
						'!*.min.js',
						'!jquery.flot*' // !jquery.flot* prevents to join all jquery.flot files in jquery.min.js
					],
					dest: '<%= dirs.js %>/admin/',
					ext: '.min.js'
				}]
			},
			adminflot: { // minify correctly the jquery.flot lib
				files: {
					'<%= dirs.js %>/admin/jquery.flot.min.js': ['<%= dirs.js %>/admin/jquery.flot.js'],
					'<%= dirs.js %>/admin/jquery.flot.pie.min.js': ['<%= dirs.js %>/admin/jquery.flot.pie.js'],
					'<%= dirs.js %>/admin/jquery.flot.resize.min.js': ['<%= dirs.js %>/admin/jquery.flot.resize.js'],
					'<%= dirs.js %>/admin/jquery.flot.stack.min.js': ['<%= dirs.js %>/admin/jquery.flot.stack.js'],
					'<%= dirs.js %>/admin/jquery.flot.time.min.js': ['<%= dirs.js %>/admin/jquery.flot.time.js'],
				}
			},
			frontend: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/frontend/',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.js %>/frontend/',
					ext: '.min.js'
				}]
			},
		},

		shell: {
			txpull: {
				options: {
					stdout: true
				},
				command: [
					'cd i18n',
					'tx pull -a -f',
				].join( '&&' )
			},
			generatemos: {
				options: {
					stdout: true
				},
				command: [
					'cd i18n/languages',
					'for i in *.po; do msgfmt $i -o ${i%%.*}.mo; done',
				].join( '&&' )
			}
		}

	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-shell' );
	grunt.loadNpmTasks( 'grunt-contrib-less' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );

	// Register tasks
	grunt.registerTask( 'default', [
		'less',
		'cssmin',
		'uglify'
	]);

	grunt.registerTask( 'dev', [
		'default',
		'shell:txpull',
		'shell:generatemos'
	]);

};
