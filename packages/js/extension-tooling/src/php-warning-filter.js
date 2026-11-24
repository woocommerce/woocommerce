/**
 * Return PHP log lines for the supplied plugin paths.
 *
 * @param {string}   log         PHP debug log contents.
 * @param {string[]} pluginPaths Path fragments that identify the tested plugins.
 * @return {string[]} Matching fatal errors, parse errors, warnings, notices and deprecations.
 */
module.exports = function filterPhpWarnings( log, pluginPaths ) {
	const paths = pluginPaths.filter(
		( pluginPath ) =>
			typeof pluginPath === 'string' && pluginPath.trim().length > 0
	);

	return log
		.split( '\n' )
		.filter(
			( line ) =>
				/PHP (Fatal error|Parse error|Warning|Notice|Deprecated)/.test(
					line
				) && paths.some( ( pluginPath ) => line.includes( pluginPath ) )
		);
};
