/*
 * Sets the experimental-iapi-mini-cart feature flag to false in both
 * client/admin/config/core.json and client/admin/config/development.json.
 */

const fs = require( 'fs' );
const path = require( 'path' );

function setFlag( filePath, key, value ) {
	const json = JSON.parse( fs.readFileSync( filePath, 'utf8' ) );
	if ( ! json.features || typeof json.features !== 'object' ) {
		json.features = {};
	}
	json.features[ key ] = value;
	fs.writeFileSync(
		filePath,
		JSON.stringify( json, null, 2 ) + '\n',
		'utf8'
	);
}

function main() {
	const pluginRoot = path.resolve( __dirname, '..' );
	const coreJson = path.join(
		pluginRoot,
		'client',
		'admin',
		'config',
		'core.json'
	);
	const devJson = path.join(
		pluginRoot,
		'client',
		'admin',
		'config',
		'development.json'
	);

	const flagKey = 'experimental-iapi-mini-cart';
	const flagValue = false;

	const files = [ coreJson, devJson ];
	files.forEach( ( file ) => {
		if ( ! fs.existsSync( file ) ) {
			throw new Error( `Config file not found: ${ file }` );
		}
	} );

	files.forEach( ( file ) => setFlag( file, flagKey, flagValue ) );

	// Output for quick verification in CI logs
	files.forEach( ( file ) => {
		const json = JSON.parse( fs.readFileSync( file, 'utf8' ) );
		// eslint-disable-next-line no-console
		console.log(
			`Set ${ flagKey } to ${
				json.features?.[ flagKey ]
			} in ${ path.relative( process.cwd(), file ) }`
		);
	} );
}

main();
