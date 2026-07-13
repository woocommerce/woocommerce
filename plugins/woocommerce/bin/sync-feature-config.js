/*
 * Copies includes/react-admin/feature-config.php into the plugin build served
 * by wp-env when WC_SHARED_PLUGIN_BUILD_PATH is set.
 *
 * In CI, jobs that consume the shared WooCommerce plugin build artifact serve
 * that artifact instead of the bind-mounted source checkout, so feature flag
 * changes made by regenerating feature-config.php in the source tree (e.g.
 * ci:legacy-minicart-flag-off) would never reach the site under test. This
 * script bridges that gap; outside CI it is a no-op.
 */

const fs = require( 'fs' );
const path = require( 'path' );

const FEATURE_CONFIG_RELATIVE_PATH = path.join(
	'includes',
	'react-admin',
	'feature-config.php'
);

function main() {
	const sharedBuildPath = process.env.WC_SHARED_PLUGIN_BUILD_PATH;

	if ( ! sharedBuildPath ) {
		// eslint-disable-next-line no-console
		console.log(
			'WC_SHARED_PLUGIN_BUILD_PATH is not set; the source checkout is served directly, nothing to sync.'
		);
		return;
	}

	const source = path.resolve(
		__dirname,
		'..',
		FEATURE_CONFIG_RELATIVE_PATH
	);
	const destination = path.join(
		sharedBuildPath,
		FEATURE_CONFIG_RELATIVE_PATH
	);

	if ( ! fs.existsSync( source ) ) {
		throw new Error( `Feature config not found: ${ source }` );
	}

	if ( ! fs.existsSync( path.dirname( destination ) ) ) {
		throw new Error(
			`Shared plugin build is missing ${ path.dirname(
				destination
			) } - is WC_SHARED_PLUGIN_BUILD_PATH pointing at a plugin root?`
		);
	}

	fs.copyFileSync( source, destination );

	// eslint-disable-next-line no-console
	console.log( `Copied ${ source } to ${ destination }` );
}

main();
