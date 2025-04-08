/*
Adapted from @automattic/webpack-rtl-plugin, that was originally adapted and released by Romain Berger under the MIT License (MIT):

MIT License

Copyright (c) 2016 Romain Berger

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

const rtlcss = require( 'rtlcss' );
const { ConcatSource } = require( 'webpack' ).sources;

const pluginName = 'WebpackRTLPlugin';

class WebpackRTLPlugin {
	constructor( options ) {
		this.options = {
			options: {},
			plugins: [],
			filenameSuffix: null,
			...options,
		};
		this.cache = new WeakMap();
	}

	apply( compiler ) {
		compiler.hooks.thisCompilation.tap( pluginName, ( compilation ) => {
			compilation.hooks.processAssets.tapPromise(
				{
					name: pluginName,
					stage: compilation.PROCESS_ASSETS_STAGE_DERIVED,
				},
				async ( assets ) => {
					const cssRe = /\.css(?:$|\?)/;
					return Promise.all(
						Array.from( compilation.chunks )
							.flatMap( ( chunk ) =>
								// Collect all files form all chunks, and generate an array of {chunk, file} objects
								Array.from( chunk.files ).map( ( asset ) => ( {
									chunk,
									asset,
								} ) )
							)
							.filter( ( { asset } ) => cssRe.test( asset ) )
							.map( async ( { chunk, asset } ) => {
								if ( this.options.test ) {
									const re = new RegExp( this.options.test );
									if ( ! re.test( asset ) ) {
										return;
									}
								}

								// Compute the filename
								const filename = asset.replace(
									cssRe,
									this.options.filenameSuffix || `.rtl$&`
								);
								const assetInstance = assets[ asset ];
								chunk.files.add( filename );

								if ( this.cache.has( assetInstance ) ) {
									const cachedRTL =
										this.cache.get( assetInstance );
									assets[ filename ] = cachedRTL;
								} else {
									const baseSource = assetInstance.source();
									const rtlSource = rtlcss.process(
										baseSource,
										this.options.options,
										this.options.plugins
									);
									// Save the asset
									assets[ filename ] = new ConcatSource(
										rtlSource
									);
									this.cache.set(
										assetInstance,
										assets[ filename ]
									);
								}
							} )
					);
				}
			);
		} );
	}
}

module.exports = WebpackRTLPlugin;
