/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	grunt.initConfig({
		// setting folder templates
		dirs: {
			css: 'assets/css',
			fonts: 'assets/fonts',
			images: 'assets/images',
			js: 'assets/js'
		},

		// Compile all .scss files.
		sass: {
			compile: {
				files: [{
					expand: true,
					cwd: '<%= dirs.css %>/',
					src: ['*.scss'],
					dest: '<%= dirs.css %>/',
					ext: '.css'
				}]
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
						'!Gruntfile.js',
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

		// Watch changes for assets
		watch: {
			less: {
				files: ['<%= dirs.css %>/*.less'],
				tasks: ['less', 'cssmin'],
			},
			js: {
				files: [
					'<%= dirs.js %>/admin/*js',
					'<%= dirs.js %>/frontend/*js',
					'!<%= dirs.js %>/admin/*.min.js',
					'!<%= dirs.js %>/frontend/*.min.js',
				],
				tasks: ['uglify']
			}
		},

		shell: {
			options: {
				stdout: true,
				stderr: true
			},
			txpull: {
				command: [
					'cd i18n',
					'tx pull -a -f',
				].join( '&&' )
			},
			generatemos: {
				command: [
					'cd i18n/languages',
					'for i in *.po; do msgfmt $i -o ${i%%.*}.mo; done'
				].join( '&&' )
			},
			generatepot: {
				command: [
					'cd i18n/makepot/',
					'sed -i "" "s/exit( \'Locked\' );/\\/\\/exit( \'Locked\' );/g" index.php',
					'php index.php generate',
					'sed -i "" "s/\\/\\/exit( \'Locked\' );/exit( \'Locked\' );/g" index.php',
				].join( '&&' )
			},
			apigen: {
				command: [
					'cd apigen/',
					'php apigen.php --source ../ --destination ../wc-apidocs --download yes --template-config ./templates/woodocs/config.neon --title "WooCommerce" --exclude "*/mijireh/*" --exclude "*/includes/libraries/*" --exclude "*/i18n/*" --exclude "*/node_modules/*" --exclude "*/deploy/*" --exclude "*/apigen/*" --exclude "*/wc-apidocs/*"',
				].join( '&&' )
			}
		},

		copy: {
			deploy: {
				src: [
					'**',
					'!.*',
					'!.*/**',
					'.htaccess',
					'!Gruntfile.js',
					'!sftp-config.json',
					'!package.json',
					'!node_modules/**',
					'!wc-apidocs/**',
					'!apigen/**'
				],
				dest: 'deploy',
				expand: true,
				dot: true
			},
		},

		clean: {
			apigen: {
				src: [ 'wc-apidocs', '.sass-cache' ]
			},
			deploy: {
				src: [ 'deploy', '.sass-cache' ]
			},
		}
	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-shell' );
	grunt.loadNpmTasks( 'grunt-contrib-sass' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );

	// Register tasks
	grunt.registerTask( 'default', [
		'sass',
		'cssmin',
		'uglify'
	]);

	// Just an alias for pot file generation
	grunt.registerTask( 'pot', [
		'shell:generatepot'
	]);

	grunt.registerTask( 'docs', [
		'clean:apigen',
		'shell:apigen'
	]);

	grunt.registerTask( 'dev', [
		'default',
		'shell:txpull',
		'shell:generatemos'
	]);

	grunt.registerTask( 'deploy', [
		'clean:deploy',
		'copy:deploy'
	]);

};
