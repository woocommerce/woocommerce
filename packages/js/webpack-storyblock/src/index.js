const fs = require( 'fs' );
const path = require( 'path' );

class WebpackStoryblockPlugin {
	constructor( options ) {
		this.options = { exclude: [ 'node_modules' ], ...options };
		this.exclude = this.options.exclude;
	}

	apply( compiler ) {
		const blockPath = path.resolve( __dirname, 'block/index.js' );

		// Dynamically add entry point
		compiler.options.entry[ 'woocommerce-storyblock' ] = {
			import: [ blockPath ],
		};

		compiler.hooks.emit.tapAsync(
			'WebpackStoryblockPlugin',
			( compilation, callback ) => {
				const context = compiler.options.context || process.cwd();
				const stories = this.findStories( context );

				console.log( 'ACTIVATION' );
				console.log( stories );

				// const storiesContent = stories.map( ( story ) => {
				// 	const storyContent = fs.readFileSync( story, 'utf-8' );
				// 	return JSON.stringify( storyContent );
				// } );

				// const indexPath = path.resolve( __dirname, 'src/index.js' );
				// const indexContent = `
				//         import React from 'react';
				//         import ReactDOM from 'react-dom';
				//         import App from './App';

				//         const stories = [${ storiesContent.join( ',' ) }];

				//         ReactDOM.render(<App stories={stories} />, document.getElementById('root'));
				//     `;

				// fs.writeFileSync( indexPath, indexContent );
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
}

module.exports = WebpackStoryblockPlugin;
