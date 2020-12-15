module.exports = function( grunt ) {
	'use strict';
	var sass = require( 'node-sass' );

	grunt.initConfig({

		// Setting folder templates.
		dirs: {
			css: 'assets/css',
			fonts: 'assets/fonts',
			images: 'assets/images',
			js: 'assets/js',
			php: 'includes'
		},

		// JavaScript linting with ESLint.
		eslint: {
			src: [
				'<%= dirs.js %>/admin/*.js',
				'!<%= dirs.js %>/admin/*.min.js',
				'<%= dirs.js %>/frontend/*.js',
				'!<%= dirs.js %>/frontend/*.min.js'
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
				ie8: true,
				parse: {
					strict: false
				},
				output: {
					comments : /@license|@preserve|^!/
				}
			},
			js_assets: {
				files: [{
					expand: true,
					cwd: '<%= dirs.js %>/',
					src: [
						'**/*.js',
						'!**/*.min.js'
					],
					extDot: 'last',
					dest: '<%= dirs.js %>',
					ext: '.min.js'
				}]
			}
		},

		// Compile all .scss files.
		sass: {
			compile: {
				options: {
					implementation: sass,
					sourceMap: 'none'
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

		// Generate RTL .css files.
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
				files: [
					{
						expand: true,
						cwd: '<%= dirs.css %>/',
						src: ['*.css'],
						dest: '<%= dirs.css %>/',
						ext: '.css'
					},
					{
						expand: true,
						cwd: '<%= dirs.css %>/photoswipe/',
						src: ['*.css', '!*.min.css'],
						dest: '<%= dirs.css %>/photoswipe/',
						ext: '.min.css'
					},
					{
						expand: true,
						cwd: '<%= dirs.css %>/photoswipe/default-skin/',
						src: ['*.css', '!*.min.css'],
						dest: '<%= dirs.css %>/photoswipe/default-skin/',
						ext: '.min.css'
					}
				]
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
				tasks: ['sass', 'rtlcss', 'postcss', 'cssmin', 'concat']
			},
			js: {
				files: [
					'GruntFile.js',
					'<%= dirs.js %>/**/*.js',
					'!<%= dirs.js %>/**/*.min.js'
				],
				tasks: ['eslint','newer:uglify']
			}
		},

		// PHP Code Sniffer.
		phpcs: {
			options: {
				bin: 'vendor/bin/phpcs'
			},
			dist: {
				src:  [
					'**/*.php', // Include all php files.
					'!includes/api/legacy/**',
					'!includes/libraries/**',
					'!node_modules/**',
					'!tests/cli/**',
					'!tmp/**',
					'!vendor/**'
				]
			}
		},

		// Autoprefixer.
		postcss: {
			options: {
				processors: [
					require( 'autoprefixer' )
				]
			},
			dist: {
				src: [
					'<%= dirs.css %>/*.css'
				]
			}
		}
	});

	// Load NPM tasks to be used here.
	grunt.loadNpmTasks( 'grunt-sass' );
	grunt.loadNpmTasks( 'grunt-phpcs' );
	grunt.loadNpmTasks( 'grunt-rtlcss' );
	grunt.loadNpmTasks( 'grunt-postcss' );
	grunt.loadNpmTasks( 'grunt-stylelint' );
	grunt.loadNpmTasks( 'gruntify-eslint' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-concat' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-newer' );

	// Register tasks.
	grunt.registerTask( 'default', [
		'js',
		'css'
	]);

	grunt.registerTask( 'js', [
		'eslint',
		'uglify:js_assets'
	]);

	grunt.registerTask( 'css', [
		'sass',
		'rtlcss',
		'postcss',
		'cssmin',
		'concat'
	]);

	grunt.registerTask( 'assets', [
		'js',
		'css'
	]);

	grunt.registerTask( 'e2e-build', [
		'uglify:js_assets',
		'css'
	]);

	// Only an alias to 'default' task.
	grunt.registerTask( 'dev', [
		'default'
	]);
};
