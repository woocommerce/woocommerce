/* eslint-disable no-console */
const fs = require( 'fs' );

const { RELEASE_TAG, ARTIFACT_NAME, WP_ENV_CONFIG_PATH } = process.env;

if ( ! RELEASE_TAG ) {
	console.error( 'Please set the RELEASE_TAG environment variable!' );
	process.exit( 1 );
}

if ( ! ARTIFACT_NAME ) {
	console.error( 'Please set the ARTIFACT_NAME environment variable!' );
	process.exit( 1 );
}

if ( ! WP_ENV_CONFIG_PATH ) {
	console.error( 'Please set the WP_ENV_CONFIG_PATH environment variable!' );
	process.exit( 1 );
}

const artifactUrl = `https://github.com/woocommerce/woocommerce/releases/download/${ RELEASE_TAG }/${ ARTIFACT_NAME }`;

console.log( `Config path: ${ WP_ENV_CONFIG_PATH }` );

const testEnvPlugins = {
	env: {
		tests: {
			plugins: [],
		},
	},
};
const data = fs.readFileSync( `${ WP_ENV_CONFIG_PATH }/.wp-env.json`, 'utf8' );
const wpEnvConfig = JSON.parse( data );
testEnvPlugins.env.tests.plugins = wpEnvConfig.env.tests.plugins;

const currentDirEntry = testEnvPlugins.env.tests.plugins.indexOf( '.' );

if ( currentDirEntry !== -1 ) {
	testEnvPlugins.env.tests.plugins[ currentDirEntry ] = artifactUrl;
}

fs.writeFileSync(
	`${ WP_ENV_CONFIG_PATH }/.wp-env.override.json`,
	JSON.stringify( testEnvPlugins, null, 2 )
);
