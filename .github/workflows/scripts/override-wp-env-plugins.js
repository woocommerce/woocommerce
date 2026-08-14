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

// wp-env names an installed plugin's folder after the source basename, so
// installing WooCommerce straight from the release URL would create a
// `woocommerce-trunk-nightly` folder - a name no real install produces and which
// breaks the test setup's `wp-content/plugins/woocommerce/...` assumptions.
// Instead, mount the artifact at the canonical `woocommerce` folder via a
// mapping (wp-env downloads and extracts release artifacts for us; locally built
// artifacts are unzipped before this script runs) and drop the source entry from
// the plugin lists. Mapped plugins are not auto-activated, so
// `tests/e2e/bin/test-env-setup.sh` activates WooCommerce explicitly.
const wooCommerceEntries = [ '.', '../woocommerce' ];
const wooCommerceMapping = {
	'wp-content/plugins/woocommerce': pluginSource,
};

// The PHP-unit jobs run against the lean `.wp-env.test.json`; the E2E/API/
// performance jobs run against the full `.wp-env.e2e.json` or one of its
// plugin-installing variants (`.wp-env.e2e.<variant>.json`, a standalone config
// whose own `plugins[]` carries the extra plugin). A given CI job starts wp-env
// with exactly one config via `--config`, and wp-env reads the sibling override
// whose basename matches. We don't know here which config the calling job uses,
// so process every config present - each gets its own sibling override, and the
// ones that aren't the job's active config are simply ignored by wp-env.
const configFiles = [
	'.wp-env.test.json',
	...fs
		.readdirSync( WP_ENV_CONFIG_PATH )
		.filter(
			( file ) =>
				// The base `.wp-env.e2e.json` and its `.wp-env.e2e.<variant>.json`
				// siblings - same shape the drift checker registers, so a
				// misnamed config can't be processed here yet slip that guard.
				/^\.wp-env\.e2e(\..+)?\.json$/.test( file ) &&
				! file.endsWith( '.override.json' )
		),
];

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
		// We write a fresh sibling override and never rewrite the source config,
		// so a re-run reads the same unfiltered config - a missing WooCommerce
		// source entry means the plugin layout changed and the artifact would not
		// land at wp-content/plugins/woocommerce. Abort.
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
		} from ${ configFile }; mapping ${ pluginSource } -> wp-content/plugins/woocommerce`
	);

	const overrideConfigPath = configPath.slice( 0, -5 ) + '.override.json';
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
