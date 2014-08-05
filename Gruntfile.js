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

		// javascript linting with jshint.
		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			all: [
				'Gruntfile.js',
				'<%= dirs.js %>/admin/*.js',
				'!<%= dirs.js %>/admin/*.min.js',
				'!<%= dirs.js %>/admin/jquery.flot*',
				'<%= dirs.js %>/frontend/*.js',
				'!<%= dirs.js %>/frontend/*.min.js'
			]
		},

		// Compile all .less files.
		less: {
			compile: {
				options: {
					// These paths are searched for @imports
					paths: ['<%= dirs.css %>/']
				},
				files: [{
					expand: true,
					cwd: '<%= dirs.css %>/',
					src: [
						'*.less',
						'!woocommerce-base.less',
						'!mixins.less'
					],
					dest: '<%= dirs.css %>/',
					ext: '.css'
				}]
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

		// Watch changes for assets
		watch: {
			less: {
				files: ['<%= dirs.css %>/*.less'],
				tasks: ['less', 'cssmin']
			},
			js: {
				files: [
					'<%= dirs.js %>/admin/*js',
					'<%= dirs.js %>/frontend/*js',
					'!<%= dirs.js %>/admin/*.min.js',
					'!<%= dirs.js %>/frontend/*.min.js'
				],
				tasks: ['uglify']
			}
		},

		makepot: {
			options: {
				type: 'wp-plugin',
				domainPath: 'i18n/languages',
				potHeaders: {
					'report-msgid-bugs-to': 'https://github.com/woothemes/woocommerce/issues',
					'language-team': 'LANGUAGE <EMAIL@ADDRESS>'
				}
			},
			frontend: {
				options: {
					potFilename: 'woocommerce.pot',
					exclude: [
						'includes/admin/.*',
						'apigen/.*'
					],
					processPot: function ( pot ) {
						pot.headers['project-id-version'] += ' Frontend';
						return pot;
					}
				}
			},
			admin: {
				options: {
					potFilename: 'woocommerce-admin.pot',
					exclude: [
						'^(?!includes\/admin).*'
					],
					processPot: function ( pot ) {
						pot.headers['project-id-version'] += ' Admin';
						return pot;
					}
				}
			}
		},

		checktextdomain: {
			options:{
				text_domain: 'woocommerce',
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
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src:  [
					'**/*.php', // Include all files
					'!apigen/**', // Exclude apigen/
					'!node_modules/**' // Exclude node_modules/
				],
				expand: true
			}
		},

		shell: {
			options: {
				stdout: true,
				stderr: true
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
			}
		},

		clean: {
			apigen: {
				src: [ 'wc-apidocs' ]
			},
			deploy: {
				src: [ 'deploy' ]
			}
		}
	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-shell' );
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-less' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-checktextdomain' );

	// Register tasks
	grunt.registerTask( 'default', [
		'less',
		'cssmin',
		'uglify'
	]);

	grunt.registerTask( 'docs', [
		'clean:apigen',
		'shell:apigen'
	]);

	grunt.registerTask( 'dev', [
		'default',
		'makepot'
	]);

	grunt.registerTask( 'deploy', [
		'clean:deploy',
		'copy:deploy'
	]);
};
