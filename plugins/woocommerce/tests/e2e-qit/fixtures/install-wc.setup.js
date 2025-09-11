/**
 * Internal dependencies
 */
import { test as setup } from './fixtures';

async function deactivateWooCommerce( restApi ) {
	try {
		await restApi.get( 'wc-admin-test-helper/live-branches/deactivate/v1' );
		console.log( 'WC deactivated.' );
	} catch ( err ) {
		console.error( 'Error deactivating WooCommerce:', err );
	}
}

async function getActivatedWooCommerceVersion( restApi ) {
	const response = await restApi.get( 'wp/v2/plugins', { status: 'active' } );
	const plugins = await response.data;
	return plugins.find( ( plugin ) => plugin.name === 'WooCommerce' )?.version;
}

setup( 'Install WC using WC Beta Tester', async ( { restApi } ) => {
	setup.skip(
		! process.env.INSTALL_WC,
		'Skipping installing WC using WC Beta Tester; INSTALL_WC not found.'
	);
	console.log( 'INSTALL_WC is enabled. Running installation script...' );

	// Check if WooCommerce is activated and its version
	const activatedWcVersion = await getActivatedWooCommerceVersion( restApi );

	if ( activatedWcVersion ) {
		console.log(
			`WooCommerce is activated. Version: ${ activatedWcVersion }`
		);
	} else {
		console.log( 'WooCommerce is not activated.' );
	}

	const wcVersion = process.env.WC_VERSION || 'latest';
	let resolvedVersion = '';

	// Install WC
	if ( wcVersion === 'latest' ) {
		const latestResponse = await restApi.post(
			'wc-admin-test-helper/live-branches/install/latest/v1',
			{ include_pre_releases: true }
		);

		if ( latestResponse.statusCode !== 200 ) {
			throw new Error(
				`Failed to install latest WC: ${ latestResponse.status() } ${ await latestResponse.text() }`
			);
		}

		resolvedVersion = ( await latestResponse.data )?.version || '';

		if ( resolvedVersion === activatedWcVersion ) {
			console.log(
				'Skip installing WC: The latest version is already installed and activated.'
			);
			return;
		}
		await deactivateWooCommerce( restApi );

		if ( ! resolvedVersion ) {
			console.error( 'Error: latestResponse.version is undefined.' );
		} else {
			console.log( `Latest version installed: ${ resolvedVersion }` );
		}
	} else {
		if ( wcVersion === activatedWcVersion ) {
			console.log(
				'Skip installing WC: The specified version is already installed and activated.'
			);
			return;
		}
		await deactivateWooCommerce( restApi );

		try {
			const downloadUrl =
				wcVersion === 'nightly'
					? 'https://github.com/woocommerce/woocommerce/releases/download/nightly/woocommerce-trunk-nightly.zip'
					: `https://github.com/woocommerce/woocommerce/releases/download/${ wcVersion }/woocommerce.zip`;

			const installResponse = await restApi.post(
				'wc-admin-test-helper/live-branches/install/v1',
				{
					pr_name: wcVersion,
					download_url: downloadUrl,
					version: wcVersion,
				}
			);

			if ( installResponse.statusCode !== 200 ) {
				throw new Error(
					`Failed to install WC ${ wcVersion }: ${ installResponse.statusCode }`
				);
			}

			resolvedVersion = wcVersion;
			console.log( `WooCommerce ${ wcVersion } installed.` );
		} catch ( err ) {
			console.error( `Error installing WC version ${ wcVersion }:`, err );
		}
	}

	// Activate WC
	if ( resolvedVersion ) {
		try {
			const activationResponse = await restApi.post(
				'wc-admin-test-helper/live-branches/activate/v1',
				{
					version: resolvedVersion,
				}
			);

			if ( activationResponse.statusCode !== 200 ) {
				throw new Error(
					`Failed to activate WC ${ resolvedVersion }: ${ activationResponse.statusCode } }`
				);
			}

			console.log( `WooCommerce ${ resolvedVersion } activated.` );
		} catch ( err ) {
			console.error(
				`Error activating WC version ${ resolvedVersion }:`,
				err
			);
		}
	} else {
		console.error(
			'Error: resolvedVersion is undefined. Skipping activation.'
		);
	}

	// Check if WooCommerce is activated and its version
	const finalActivatedWcVersion = await getActivatedWooCommerceVersion(
		restApi
	);

	if (
		wcVersion === 'nightly'
			? finalActivatedWcVersion.endsWith( '-dev' )
			: finalActivatedWcVersion === resolvedVersion
	) {
		console.log(
			`Installing WC ${ finalActivatedWcVersion } with WC Beta Tester is finished.`
		);
	} else {
		console.error(
			`Expected WC version ${ resolvedVersion } is not installed. Instead: ${ finalActivatedWcVersion }`
		);
	}
} );
