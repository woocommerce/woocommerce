/**
 * Return PHP log lines for the supplied plugin paths.
 *
 * @param {string}   log         PHP debug log contents.
 * @param {string[]} pluginPaths Path fragments that identify the tested plugins.
 * @return {string[]} Matching fatal errors, parse errors, warnings, notices and deprecations.
 */
module.exports = function filterPhpWarnings( log, pluginPaths ) {
	return log
		.split( '\n' )
		.filter(
			( line ) =>
				/PHP (Fatal error|Parse error|Warning|Notice|Deprecated)/.test(
					line
				) &&
				pluginPaths.some(
					( pluginPath ) =>
						pluginPath !== '' && line.includes( pluginPath )
				)
		);
};
