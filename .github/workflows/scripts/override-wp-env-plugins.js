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
const wooCommerceMapping = {
	'wp-content/plugins/woocommerce': artifactUrl,
};

// The PHP-unit jobs run against the lean `.wp-env.test.json`; the E2E/API/
// performance jobs run against the full `.wp-env.e2e.json`. A given CI job starts
// wp-env with exactly one of these via `--config`, and wp-env reads the override
// file whose basename matches (`.wp-env.<name>.override.json`). We don't know here
// which config the calling job uses, so write an override for every config present
// - the ones that aren't the job's active config are simply ignored by wp-env.
const configFiles = [ '.wp-env.test.json', '.wp-env.e2e.json' ];

let processed = 0;

for ( const configFile of configFiles ) {
	const configPath = `${ WP_ENV_CONFIG_PATH }/${ configFile }`;
	if ( ! fs.existsSync( configPath ) ) {
		console.log( `Skipping ${ configPath } (not found)` );
		continue;
	}

	console.log( `Reading ${ configPath }` );
	const wpEnvConfig = JSON.parse( fs.readFileSync( configPath, 'utf8' ) );

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

	// These are single-container-set configs (testsEnvironment: false), so plugins
	// and mappings live at the top level - no env.tests nesting.
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
		} from ${ configFile }; mapping ${ artifactUrl } -> wp-content/plugins/woocommerce`
	);

	const overrideConfigPath = configPath.endsWith( '.json' )
		? configPath.slice( 0, -5 ) + '.override.json'
		: configPath;
	console.log( `Saving ${ overrideConfigPath }` );
	fs.writeFileSync(
		overrideConfigPath,
		JSON.stringify( overrideConfig, null, 2 )
	);
	processed++;
}

if ( processed === 0 ) {
	console.error(
		`No wp-env config files (${ configFiles.join(
			', '
		) }) found under ${ WP_ENV_CONFIG_PATH }. Aborting.`
	);
	process.exit( 1 );
}
