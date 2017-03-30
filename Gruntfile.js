/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	grunt.initConfig({

		// Setting folder templates.
		dirs: {
			css: 'assets/css',
			fonts: 'assets/fonts',
			images: 'assets/images',
			js: 'assets/js'
		},

		// JavaScript linting with JSHint.
		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			all: [
				'Gruntfile.js',
				'<%= dirs.js %>/admin/*.js',
				'!<%= dirs.js %>/admin/*.min.js',
				'<%= dirs.js %>/frontend/*.js',
				'!<%= dirs.js %>/frontend/*.min.js',
				'includes/gateways/simplify-commerce/assets/js/*.js',
				'!includes/gateways/simplify-commerce/assets/js/*.min.js'
			]
		},

		// Sass linting with Stylelint.
		stylelint: {
			options: {
				configFile: '.stylelintrc'
			},
			all: [
				'<%= dirs.css %>/*.scss',
				'!<%= dirs.css %>/select2.scss'
			]
		},

		// Minify .js files.
		uglify: {
			options: {
				// Preserve comments that start with a bang.
				preserveComments: /^!/
			},
			admin: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/admin/',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: '<%= dirs.js %>/admin/',
					ext: '.min.js'
				}]
			},
			vendor: {
				files: {
					'<%= dirs.js %>/accounting/accounting.min.js': ['<%= dirs.js %>/accounting/accounting.js'],
					'<%= dirs.js %>/jquery-blockui/jquery.blockUI.min.js': ['<%= dirs.js %>/jquery-blockui/jquery.blockUI.js'],
					'<%= dirs.js %>/jquery-cookie/jquery.cookie.min.js': ['<%= dirs.js %>/jquery-cookie/jquery.cookie.js'],
					'<%= dirs.js %>/js-cookie/js.cookie.min.js': ['<%= dirs.js %>/js-cookie/js.cookie.js'],
					'<%= dirs.js %>/jquery-flot/jquery.flot.min.js': ['<%= dirs.js %>/jquery-flot/jquery.flot.js'],
					'<%= dirs.js %>/jquery-flot/jquery.flot.pie.min.js': ['<%= dirs.js %>/jquery-flot/jquery.flot.pie.js'],
					'<%= dirs.js %>/jquery-flot/jquery.flot.resize.min.js': ['<%= dirs.js %>/jquery-flot/jquery.flot.resize.js'],
					'<%= dirs.js %>/jquery-flot/jquery.flot.stack.min.js': ['<%= dirs.js %>/jquery-flot/jquery.flot.stack.js'],
					'<%= dirs.js %>/jquery-flot/jquery.flot.time.min.js': ['<%= dirs.js %>/jquery-flot/jquery.flot.time.js'],
					'<%= dirs.js %>/jquery-payment/jquery.payment.min.js': ['<%= dirs.js %>/jquery-payment/jquery.payment.js'],
					'<%= dirs.js %>/jquery-qrcode/jquery.qrcode.min.js': ['<%= dirs.js %>/jquery-qrcode/jquery.qrcode.js'],
					'<%= dirs.js %>/jquery-serializejson/jquery.serializejson.min.js': ['<%= dirs.js %>/jquery-serializejson/jquery.serializejson.js'],
					'<%= dirs.js %>/jquery-tiptip/jquery.tipTip.min.js': ['<%= dirs.js %>/jquery-tiptip/jquery.tipTip.js'],
					'<%= dirs.js %>/jquery-ui-touch-punch/jquery-ui-touch-punch.min.js': ['<%= dirs.js %>/jquery-ui-touch-punch/jquery-ui-touch-punch.js'],
					'<%= dirs.js %>/prettyPhoto/jquery.prettyPhoto.init.min.js': ['<%= dirs.js %>/prettyPhoto/jquery.prettyPhoto.init.js'],
					'<%= dirs.js %>/prettyPhoto/jquery.prettyPhoto.min.js': ['<%= dirs.js %>/prettyPhoto/jquery.prettyPhoto.js'],
					'<%= dirs.js %>/flexslider/jquery.flexslider.min.js': ['<%= dirs.js %>/flexslider/jquery.flexslider.js'],
					'<%= dirs.js %>/zoom/jquery.zoom.min.js': ['<%= dirs.js %>/zoom/jquery.zoom.js'],
					'<%= dirs.js %>/photoswipe/photoswipe.min.js': ['<%= dirs.js %>/photoswipe/photoswipe.js'],
					'<%= dirs.js %>/photoswipe/photoswipe-ui-default.min.js': ['<%= dirs.js %>/photoswipe/photoswipe-ui-default.js'],
					'<%= dirs.js %>/round/round.min.js': ['<%= dirs.js %>/round/round.js'],
					'<%= dirs.js %>/select2/select2.min.js': ['<%= dirs.js %>/select2/select2.js'],
					'<%= dirs.js %>/stupidtable/stupidtable.min.js': ['<%= dirs.js %>/stupidtable/stupidtable.js'],
					'<%= dirs.js %>/zeroclipboard/jquery.zeroclipboard.min.js': ['<%= dirs.js %>/zeroclipboard/jquery.zeroclipboard.js']
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
			simplify_commerce: {
				files: [{
					expand: true,
					cwd: 'includes/gateways/simplify-commerce/assets/js/',
					src: [
						'*.js',
						'!*.min.js'
					],
					dest: 'includes/gateways/simplify-commerce/assets/js/',
					ext: '.min.js'
				}]
			}
		},

		// Compile all .scss files.
		sass: {
			compile: {
				options: {
					sourcemap: 'none',
					loadPath: require( 'node-bourbon' ).includePaths
				},
				files: [{
					expand: true,
					cwd: '<%= dirs.css %>/',
					src: ['*.scss'],
					dest: '<%= dirs.css %>/',
					ext: '.css'
				}]
			}
		},

		// Generate RTL .css files
		rtlcss: {
			woocommerce: {
				expand: true,
				cwd: '<%= dirs.css %>',
				src: [
					'*.css',
					'!select2.css',
					'!*-rtl.css'
				],
				dest: '<%= dirs.css %>/',
				ext: '-rtl.css'
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

		// Concatenate select2.css onto the admin.css files.
		concat: {
			admin: {
				files: {
					'<%= dirs.css %>/admin.css' : ['<%= dirs.css %>/select2.css', '<%= dirs.css %>/admin.css'],
					'<%= dirs.css %>/admin-rtl.css' : ['<%= dirs.css %>/select2.css', '<%= dirs.css %>/admin-rtl.css']
				}
			}
		},

		// Watch changes for assets.
		watch: {
			css: {
				files: ['<%= dirs.css %>/*.scss'],
				tasks: ['sass', 'rtlcss', 'cssmin', 'concat']
			},
			js: {
				files: [
					'<%= dirs.js %>/admin/*js',
					'<%= dirs.js %>/frontend/*js',
					'!<%= dirs.js %>/admin/*.min.js',
					'!<%= dirs.js %>/frontend/*.min.js'
				],
				tasks: ['jshint', 'uglify']
			}
		},

		// Generate POT files.
		makepot: {
			options: {
				type: 'wp-plugin',
				domainPath: 'i18n/languages',
				potHeaders: {
					'report-msgid-bugs-to': 'https://github.com/woocommerce/woocommerce/issues',
					'language-team': 'LANGUAGE <EMAIL@ADDRESS>'
				}
			},
			dist: {
				options: {
					potFilename: 'woocommerce.pot',
					exclude: [
						'apigen/.*',
						'tests/.*',
						'tmp/.*'
					]
				}
			}
		},

		// Check textdomain errors.
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
					'**/*.php',         // Include all files
					'!apigen/**',       // Exclude apigen/
					'!node_modules/**', // Exclude node_modules/
					'!tests/**',        // Exclude tests/
					'!vendor/**',       // Exclude vendor/
					'!tmp/**'           // Exclude tmp/
				],
				expand: true
			}
		},

		// Exec shell commands.
		shell: {
			options: {
				stdout: true,
				stderr: true
			},
			apigen: {
				command: [
					'apigen generate -q',
					'cd apigen',
					'php hook-docs.php'
				].join( '&&' )
			},
			e2e_test: {
				command: 'npm run --silent test:single tests/e2e-tests/' + grunt.option( 'file' )
			},
			e2e_tests: {
				command: 'npm run --silent test'
			}
		},

		// Clean the directory.
		clean: {
			apigen: {
				src: [ 'wc-apidocs' ]
			}
		},

		// PHP Code Sniffer.
		phpcs: {
			options: {
				bin: 'vendor/bin/phpcs',
				standard: './phpcs.ruleset.xml'
			},
			dist: {
				src:  [
					'**/*.php',                                                  // Include all files
					'!apigen/**',                                                // Exclude apigen/
					'!includes/api/legacy/**',                                   // Exclude legacy REST API
					'!includes/gateways/simplify-commerce/includes/Simplify/**', // Exclude simplify commerce SDK
					'!includes/libraries/**',                                    // Exclude libraries/
					'!node_modules/**',                                          // Exclude node_modules/
					'!tests/cli/**',                                             // Exclude tests/cli/
					'!tmp/**',                                                   // Exclude tmp/
					'!vendor/**'                                                 // Exclude vendor/
				]
			}
		}
	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-shell' );
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-rtlcss' );
	grunt.loadNpmTasks( 'grunt-checktextdomain' );
	grunt.loadNpmTasks( 'grunt-contrib-jshint' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-sass' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-concat' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-stylelint' );
	grunt.loadNpmTasks( 'grunt-phpcs' );

	// Register tasks
	grunt.registerTask( 'default', [
		'jshint',
		'uglify',
		'css'
	]);

	grunt.registerTask( 'js', [
		'jshint',
		'uglify:admin',
		'uglify:frontend'
	]);

	grunt.registerTask( 'css', [
		'sass',
		'rtlcss',
		'cssmin',
		'concat'
	]);

	grunt.registerTask( 'docs', [
		'clean:apigen',
		'shell:apigen'
	]);

	grunt.registerTask( 'dev', [
		'default',
		'makepot'
	]);

	grunt.registerTask( 'e2e-tests', [
		'shell:e2e_tests'
	]);

	grunt.registerTask( 'e2e-test', [
		'shell:e2e_test'
	]);
};
