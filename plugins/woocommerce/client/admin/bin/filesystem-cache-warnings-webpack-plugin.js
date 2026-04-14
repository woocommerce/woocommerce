// ExternalModule instances (wp.*, wc.* externals) are not serializable by webpack's PackFileCacheStrategy.
// Suppress here since ignoreWarnings only affects compilation warnings, not infrastructure logs.

function FilesystemCacheWarningsPlugin() {}

FilesystemCacheWarningsPlugin.prototype.apply = function ( compiler ) {
	compiler.hooks.infrastructureLog.tap(
		'SuppressExternalModuleCacheWarning',
		( name, type, args ) => {
			if ( type === 'warn' && name === 'webpack.cache.PackFileCacheStrategy' ) {
				return (
					args[ 0 ]?.includes?.( 'No serializer registered for ModuleExternalInitFragment' ) ||
					args[ 0 ]?.includes?.( 'No serializer registered for ExternalModule' ) ||
					args[ 0 ]?.includes?.( 'No serializer registered for Warning' )
				);
			}
		}
	);
};

module.exports = FilesystemCacheWarningsPlugin;
