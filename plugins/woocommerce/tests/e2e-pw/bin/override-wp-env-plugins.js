const fs = require( 'fs' );

const { RELEASE_TAG, ARTIFACT_NAME } = process.env;
const artifactUrl = `https://github.com/woocommerce/woocommerce/releases/download/${ RELEASE_TAG }/${ ARTIFACT_NAME }`;
// https://github.com/woocommerce/woocommerce/releases/download/nightly/woocommerce-trunk-nightly.zip
// https://github.com/woocommerce/woocommerce/releases/download/9.0.0-beta.2/woocommerce.zip

const testEnvPlugins = {
	env: {
		tests: {
			plugins: [],
		},
	},
};
const data = fs.readFileSync( '.wp-env.json', 'utf8' );
const wpEnvConfig = JSON.parse( data );
testEnvPlugins.env.tests.plugins = wpEnvConfig.env.tests.plugins;

const currentDirEntry = testEnvPlugins.env.tests.plugins.indexOf( '.' );

if ( currentDirEntry !== -1 ) {
	testEnvPlugins.env.tests.plugins[ currentDirEntry ] = artifactUrl;
}

fs.writeFileSync(
	'.wp-env.override.json',
	JSON.stringify( testEnvPlugins, null, 2 )
);
