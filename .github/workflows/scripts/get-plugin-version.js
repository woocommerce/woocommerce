const { readFile } = require( 'fs/promises' );
const { join } = require( 'path' );

exports.getVersion = async plugin => {
	const filePath = join(
		process.env.GITHUB_WORKSPACE,
		`plugins/${ plugin }/${ plugin }.php`
	);
	const pluginFileContents = await readFile( filePath, 'utf8' );
	const versionMatch = pluginFileContents.match( /Version: (\d+\.\d+\.\d+.*)\n/m );
	return versionMatch && versionMatch[1];
};

