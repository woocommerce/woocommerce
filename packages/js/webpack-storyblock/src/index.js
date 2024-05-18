const fs = require( 'fs' );
const path = require( 'path' );

class WebpackStoryblockPlugin {
	constructor( options ) {
		this.buildDir = path.resolve( __dirname, '../build' );

		// TODO - user should be able to provide their own entry point.
		const blockPath = path.resolve( this.buildDir, 'block/index.js' );

		this.options = {
			exclude: [ 'node_modules' ],
			entryName: 'woocommerce-storyblock',
			blockEntry: blockPath,
			...options,
		};

		// TODO - validate options.
		this.exclude = this.options.exclude;
		this.entryName = this.options.entryName;
		this.blockEntry = this.options.blockEntry;
		this.storyIndexPath = path.resolve(
			__dirname,
			'../build/block/story-index.js'
		);
	}

	makeBuildDirectories() {
		const blockDir = path.resolve( this.buildDir, 'block' );

		if ( ! fs.existsSync( blockDir ) ) {
			fs.mkdirSync( blockDir, {
				recursive: true,
			} );
		}

		if ( ! fs.existsSync( path.join( blockDir, 'stories' ) ) ) {
			fs.mkdirSync( path.join( blockDir, 'stories' ), {
				recursive: true,
			} );
		}

		// clear the directories
		fs.rmdirSync( blockDir, { recursive: true } );
		fs.mkdirSync( path.join( blockDir, 'stories' ), {
			recursive: true,
		} );

		const blockAssets = fs.readdirSync(
			path.resolve( __dirname, 'block' )
		);

		blockAssets.forEach( ( asset ) => {
			const assetPath = path.resolve( __dirname, 'block', asset );
			const assetContent = fs.readFileSync( assetPath );

			fs.writeFileSync(
				path.resolve( __dirname, '../build/block', asset ),
				assetContent
			);
		} );

		// make the file
		fs.writeFileSync( this.storyIndexPath, '' );
	}

	apply( compiler ) {
		const logger = compiler.getInfrastructureLogger(
			'WebpackStoryblockPlugin'
		);
		const context = compiler.options.context || process.cwd();
		const stories = this.findStories( context );

		this.makeBuildDirectories();

		const outputPath = compiler.options.output.path;
		const blockDir = path.resolve( outputPath, 'storybook-blocks/block' );

		const indexContent = stories
			.map( ( story ) => {
				return `import '${ path
					.relative( blockDir, story )
					.replace( /\\/g, '/' ) }';`;
			} )
			.join( '\n' );

		// now write the index file.
		fs.writeFileSync( this.storyIndexPath, indexContent );

		// make the build dir in the output path
		fs.mkdirSync( path.resolve( outputPath, 'storybook-blocks' ), {
			recursive: true,
		} );

		// clear the directory
		fs.rmdirSync( path.resolve( outputPath, 'storybook-blocks' ), {
			recursive: true,
		} );

		// copy the build dir
		this.copyFiles(
			this.buildDir,
			path.join( outputPath, 'storybook-blocks' )
		);

		// Dynamically add entry points - we dynamically import the stories,
		// so we force webpack to build them here with an index as an entry point.
		compiler.options.entry[ `${ this.entryName }-story-index` ] = {
			import: [
				path.join(
					outputPath,
					'storybook-blocks',
					'block',
					'story-index.js'
				),
			],
		};

		compiler.options.entry[ this.entryName ] = {
			import: [ this.blockEntry ],
		};

		// All this needs to be done before compilation, because we want the user's webpack config
		// to compile these files as if they were part of the project.

		compiler.hooks.emit.tapAsync(
			'WebpackStoryblockPlugin',
			( compilation, callback ) => {
				callback();
			}
		);
	}

	findStories( dir, fileList = [] ) {
		const files = fs.readdirSync( dir );

		files.forEach( ( file ) => {
			const filePath = path.join( dir, file );
			const stat = fs.statSync( filePath );

			if ( stat.isDirectory() ) {
				if (
					! this.exclude.some( ( excludedDir ) =>
						filePath.includes( excludedDir )
					)
				) {
					this.findStories( filePath, fileList );
				}
			} else if ( file.match( /\.stories\.[jt]sx?$/ ) ) {
				fileList.push( filePath );
			}
		} );

		return fileList;
	}

	copyFiles( srcDir, destDir ) {
		const entries = fs.readdirSync( srcDir, { withFileTypes: true } );

		entries.forEach( ( entry ) => {
			const srcPath = path.join( srcDir, entry.name );
			const destPath = path.join( destDir, entry.name );

			if ( entry.isDirectory() ) {
				// Create directory if it doesn't exist
				if ( ! fs.existsSync( destPath ) ) {
					fs.mkdirSync( destPath, { recursive: true } );
				}
				// Recurse into the directory
				this.copyFiles( srcPath, destPath );
			} else {
				// Copy file
				fs.copyFileSync( srcPath, destPath );
			}
		} );
	}
}

module.exports = WebpackStoryblockPlugin;
