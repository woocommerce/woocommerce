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
					'!<%= dirs.js %>/frontend/*.min.js',
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
					'!node_modules/**'
				],
				dest: '_deploy',
				expand: true,
				dot: true
			},
		},

		clean: {
			deploy: {
				src: [ '_deploy' ]
			},
		},

		wp_deploy: {
	        deploy: { 
	            options: {
	                plugin_slug: 'woocommerce',   
	                build_dir: '_deploy'
	            },
	        }
	    }		

	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-shell' );
	grunt.loadNpmTasks( 'grunt-contrib-less' );
	grunt.loadNpmTasks( 'grunt-contrib-cssmin' );
	grunt.loadNpmTasks( 'grunt-contrib-uglify' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-wp-deploy' );

	// Register tasks
	grunt.registerTask( 'default', [
		'less',
		'cssmin',
		'uglify'
	]);

	// Just an alias for pot file generation
	grunt.registerTask( 'pot', [
		'shell:generatepot'
	]);

	grunt.registerTask( 'dev', [
		'default',
		'shell:txpull',
		'shell:generatemos'
	]);

	grunt.registerTask( 'deploy', [ 
		'clean:deploy', 
		'copy:deploy',
		'wp_deploy'
	]);

};
