/* eslint-disable no-console */
const fs = require( 'fs' );

const { RELEASE_TAG, ARTIFACT_NAME, PLUGIN_PATH, WP_ENV_CONFIG_PATH } =
	process.env;

if ( ! PLUGIN_PATH && ! RELEASE_TAG ) {
	console.error(
		'Please set the PLUGIN_PATH or RELEASE_TAG environment variable!'
	);
	process.exit( 1 );
}

if ( ! PLUGIN_PATH && ! ARTIFACT_NAME ) {
	console.error(
		'Please set the PLUGIN_PATH or ARTIFACT_NAME environment variable!'
	);
	process.exit( 1 );
}

if ( ! WP_ENV_CONFIG_PATH ) {
	console.error( 'Please set the WP_ENV_CONFIG_PATH environment variable!' );
	process.exit( 1 );
}

const pluginSource =
	PLUGIN_PATH ||
	`https://github.com/woocommerce/woocommerce/releases/download/${ RELEASE_TAG }/${ ARTIFACT_NAME }`;

const configPath = `${ WP_ENV_CONFIG_PATH }/.wp-env.json`;
console.log( `Reading ${ configPath }` );
const data = fs.readFileSync( configPath, 'utf8' );
const wpEnvConfig = JSON.parse( data );

const overrideConfig = {};

if ( wpEnvConfig.plugins ) {
	overrideConfig.plugins = wpEnvConfig.plugins;
}

if ( wpEnvConfig.env?.tests?.plugins ) {
	overrideConfig.env = {
		tests: {
			plugins: wpEnvConfig.env.tests.plugins,
		},
	};
}

const entriesToReplace = [ '.', '../woocommerce', '../..' ];

for ( const entry of entriesToReplace ) {
	// Search and replace in root plugins
	let found = overrideConfig.plugins?.indexOf( entry ) ?? -1;
	if ( found >= 0 ) {
		console.log(
			`Replacing ${ entry } with ${ pluginSource } in root plugins`
		);
		overrideConfig.plugins[ found ] = pluginSource;
	}

	// Search and replace in test env plugins
	found = overrideConfig.env?.tests?.plugins?.indexOf( entry ) ?? -1;
	if ( found >= 0 ) {
		console.log(
			`Replacing ${ entry } with ${ pluginSource } in env.tests.plugins`
		);
		overrideConfig.env.tests.plugins[ found ] = pluginSource;
	}
}

const overrideConfigPath = `${ WP_ENV_CONFIG_PATH }/.wp-env.override.json`;
console.log( `Saving ${ overrideConfigPath }` );
fs.writeFileSync(
	overrideConfigPath,
	JSON.stringify( overrideConfig, null, 2 )
);
