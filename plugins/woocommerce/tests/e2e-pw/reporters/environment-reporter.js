require( '@playwright/test/reporter' );
const fs = require( 'fs' );
const path = require( 'path' );
const { wpCLI } = require( '../utils/wp-cli' );

class EnvironmentReporter {
	constructor( options ) {
		this.reportOptions = options;
	}

	async onEnd() {
		console.log( 'Getting environment details' );
		const { outputFolder } = this.reportOptions;

		if ( ! outputFolder ) {
			console.log( 'No output folder specified!' );
			return;
		}

		const { USE_WP_ENV, CI } = process.env;

		let environmentData = `CI=${ CI }\nUSE_WP_ENV=${ USE_WP_ENV }`;

		if ( USE_WP_ENV ) {
			try {
				// Get WooCommerce version
				const woocommerceData = await wpCLI(
					'plugin get woocommerce --format=json'
				);

				console.log( woocommerceData );

				if ( woocommerceData?.version ) {
					environmentData += `\nWooCommerce=${ woocommerceData.version }`;
				}
			} catch ( err ) {
				console.error( `Error getting environment details: ${ err }` );
			}
		}

		const filePath = path.resolve( outputFolder, 'environment.properties' );

		try {
			console.log( environmentData );
			fs.writeFileSync( filePath, environmentData );
		} catch ( err ) {
			console.error( `Error writing environment.properties: ${ err }` );
		}
	}
}

module.exports = EnvironmentReporter;
