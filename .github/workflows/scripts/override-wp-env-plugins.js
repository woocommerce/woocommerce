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

const configPath = `${ WP_ENV_CONFIG_PATH }/.wp-env.test.json`;
console.log( `Reading ${ configPath }` );
const wpEnvConfig = JSON.parse( fs.readFileSync( configPath, 'utf8' ) );

// wp-env names an installed plugin's folder after the source basename, so
// installing WooCommerce straight from the release URL would create a
// `woocommerce-trunk-nightly` folder - a name no real install produces and which
// breaks the test setup's `wp-content/plugins/woocommerce/...` assumptions.
// Instead, mount the release artifact at the canonical `woocommerce` folder via a
// mapping (wp-env downloads and extracts it for us; the zip's top-level dir is
// `woocommerce/`, the same one WordPress core unzips for a real user) and drop
// the source entry from the plugin lists. Mapped plugins are not auto-activated,
// so `tests/e2e/bin/test-env-setup.sh` activates WooCommerce explicitly.
const wooCommerceEntries = [ '.', '../woocommerce' ];

let removed = 0;
const withoutWooCommerce = ( plugins ) => {
	if ( ! Array.isArray( plugins ) ) {
		return plugins;
	}
	const filtered = plugins.filter(
		( entry ) => ! wooCommerceEntries.includes( entry )
	);
	removed += plugins.length - filtered.length;
	return filtered;
};

const wooCommerceMapping = {
	'wp-content/plugins/woocommerce': artifactUrl,
};

// `.wp-env.test.json` is a single-container-set config (testsEnvironment:
// false), so plugins and mappings live at the top level - no env.tests nesting.
const overrideConfig = {
	plugins: withoutWooCommerce( wpEnvConfig.plugins ),
	mappings: wooCommerceMapping,
};

if ( removed === 0 ) {
	console.error(
		`No WooCommerce source entry (${ wooCommerceEntries.join(
			' or '
		) }) found in ${ configPath }. The artifact would not land at ` +
			`wp-content/plugins/woocommerce - the plugin layout likely changed. Aborting.`
	);
	process.exit( 1 );
}

console.log(
	`Removed ${ removed } WooCommerce source entr${
		removed === 1 ? 'y' : 'ies'
	}; mapping ${ artifactUrl } -> wp-content/plugins/woocommerce`
);

const overrideConfigPath = `${ WP_ENV_CONFIG_PATH }/.wp-env.test.override.json`;
console.log( `Saving ${ overrideConfigPath }` );
fs.writeFileSync(
	overrideConfigPath,
	JSON.stringify( overrideConfig, null, 2 )
);
