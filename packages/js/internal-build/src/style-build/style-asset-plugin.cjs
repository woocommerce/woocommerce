/**
 * Add an asset file for each entry point that contains the current version calculated for the current source code.
 *
 * This is modified from WP dependency-extraction-webpack-plugin plugin:
 * https://github.com/WordPress/gutenberg/tree/a04a8e94e8b93ba60441c6534e21f4c3c26ff1bc/packages/dependency-extraction-webpack-plugin
 *
 * We can contribute this back to the original plugin in the future and remove this file.
 */

/**
 * External dependencies
 */
const path = require( 'path' );
const json2php = require( 'json2php' );

class AssetDataPlugin {
	constructor( options ) {
		this.options = Object.assign(
			{
				combineAssets: false,
				combinedOutputFile: null,
				outputFormat: 'php',
				outputFilename: null,
			},
			options
		);
	}

	/**
	 * @param {any} asset Asset Data
	 * @return {string} Stringified asset data suitable for output
	 */
	stringify( asset ) {
		if ( this.options.outputFormat === 'php' ) {
			return `<?php return ${ json2php(
				JSON.parse( JSON.stringify( asset ) )
			) };\n`;
		}

		return JSON.stringify( asset );
	}

	apply( compiler ) {
		const { createHash } = compiler.webpack.util;
		const { RawSource } = compiler.webpack.sources;

		compiler.hooks.thisCompilation.tap(
			this.constructor.name,
			( compilation ) => {
				compilation.hooks.processAssets.tap(
					{
						name: this.constructor.name,
						stage: compiler.webpack.Compilation
							.PROCESS_ASSETS_STAGE_ANALYSE,
					},
					() => this.addAssets( compilation, createHash, RawSource )
				);
			}
		);
	}

	/** @param {webpack.Compilation} compilation */
	addAssets( compilation, createHash, RawSource ) {
		const {
			combineAssets,
			combinedOutputFile,
			outputFormat,
			outputFilename,
		} = this.options;

		const combinedAssetsData = {};

		// Accumulate all entrypoint chunks, some of them shared
		const entrypointChunks = new Set();
		for ( const entrypoint of compilation.entrypoints.values() ) {
			for ( const chunk of entrypoint.chunks ) {
				entrypointChunks.add( chunk );
			}
		}

		// Process each entrypoint chunk independently
		for ( const chunk of entrypointChunks ) {
			const chunkFiles = Array.from( chunk.files );

			const styleExtensionRegExp = /\.s?css$/i;

			const chunkStyleFile = chunkFiles.find( ( f ) =>
				styleExtensionRegExp.test( f )
			);
			if ( ! chunkStyleFile ) {
				// No style file, skip
				continue;
			}

			// Go through the assets and hash the sources. We can't just use
			// `chunk.contentHash` because that's not updated when
			// assets are minified. In practice the hash is updated by
			// `RealContentHashPlugin` after minification, but it only modifies
			// already-produced asset filenames and the updated hash is not
			// available to plugins.
			const { hashFunction, hashDigest, hashDigestLength } =
				compilation.outputOptions;

			const contentHash = chunkFiles
				.filter( ( f ) => styleExtensionRegExp.test( f ) )
				.sort()
				.reduce( ( hash, filename ) => {
					const asset = compilation.getAsset( filename );
					return hash.update( asset.source.buffer() );
				}, createHash( hashFunction ) )
				.digest( hashDigest )
				.slice( 0, hashDigestLength );

			const assetData = {
				version: contentHash,
			};

			if ( combineAssets ) {
				combinedAssetsData[ chunkStyleFile ] = assetData;
				continue;
			}

			let assetFilename;
			if ( outputFilename ) {
				assetFilename = compilation.getPath( outputFilename, {
					chunk,
					filename: chunkStyleFile,
					contentHash,
				} );
			} else {
				const suffix =
					'.asset.' + ( outputFormat === 'php' ? 'php' : 'json' );
				assetFilename = compilation
					.getPath( '[file]', { filename: chunkStyleFile } )
					.replace( styleExtensionRegExp, suffix );
			}

			// Add source and file into compilation for webpack to output.
			compilation.assets[ assetFilename ] = new RawSource(
				this.stringify( assetData )
			);
			chunk.files.add( assetFilename );
		}

		if ( combineAssets ) {
			const outputFolder = compilation.outputOptions.path;

			const assetsFilePath = path.resolve(
				outputFolder,
				combinedOutputFile ||
					'assets.' + ( outputFormat === 'php' ? 'php' : 'json' )
			);
			const assetsFilename = path.relative(
				outputFolder,
				assetsFilePath
			);

			// Add source into compilation for webpack to output.
			compilation.assets[ assetsFilename ] = new RawSource(
				this.stringify( combinedAssetsData )
			);
		}
	}
}

module.exports = AssetDataPlugin;
