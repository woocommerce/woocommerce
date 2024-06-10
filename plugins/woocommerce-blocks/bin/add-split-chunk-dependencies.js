// Dependency Exraction Webpack Plugin's asset.php array entries always refer to a single module with a single corresponding
// js file. When we generate a split chunk it can represent multiple modules but it becomes an essential dependency
// for any module in the bundle. To get around this limitation, we prefix the split chunk handles to the asset.php
// file's dependency array. You must still register the handles for them in PHP for this to work but you won't have
// to enqueue the split chunks per entry-point for example.
class AddSplitChunkDependencies {
	apply( compiler ) {
		compiler.hooks.thisCompilation.tap(
			'AddStableChunksToAssets',
			( compilation, callback ) => {
				compilation.hooks.processAssets.tap(
					{
						name: 'AddStableChunksToAssets',
						stage: compiler.webpack.Compilation
							.PROCESS_ASSETS_STAGE_ANALYSE,
					},
					() => {
						const { chunks } = compilation;

						const splitChunks = chunks.filter( ( chunk ) => {
							return chunk?.chunkReason?.includes( 'split' );
						} );

						// find files that have an asset.php file
						const chunksToAddSplitsTo = chunks.filter(
							( chunk ) => {
								return (
									! chunk?.chunkReason?.includes( 'split' ) &&
									chunk.files.find( ( file ) =>
										file.endsWith( 'asset.php' )
									)
								);
							}
						);

						for ( const chunk of chunksToAddSplitsTo ) {
							const assetFile = chunk.files.find( ( file ) =>
								file.endsWith( 'asset.php' )
							);

							const assetFileContent = compilation.assets[
								assetFile
							]
								.source()
								.toString();

							const extraDependencies = splitChunks
								.map( ( c ) => `'${ c.name }'` )
								.join( ', ' );

							const updatedFileContent = assetFileContent.replace(
								/('dependencies'\s*=>\s*array\s*\(\s*)([^)]*)\)/,
								`$1${ extraDependencies }, $2)`
							);

							compilation.assets[ assetFile ] = {
								source: () => updatedFileContent,
								size: () => updatedFileContent.length,
							};
						}
					}
				);
			}
		);
	}
}

module.exports = AddSplitChunkDependencies;
