/**
 * Internal dependencies
 */
import { filterBusinessExtensions } from '../flows/selective-bundle';

describe( 'BusinessDetails', () => {
	test( 'filtering extensions', () => {
		const extensions = {
			'creative-mail-by-constant-contact': true,
			'facebook-for-woocommerce': true,
			install_extensions: true,
			jetpack: true,
			'google-listings-and-ads': true,
			'mailchimp-for-woocommerce': true,
			'woocommerce-payments': true,
			'woocommerce-services:shipping': true,
			'woocommerce-services:tax': true,
		};

		const expectedExtensions = [
			'creative-mail-by-constant-contact',
			'facebook-for-woocommerce',
			'jetpack',
			'google-listings-and-ads',
			'mailchimp-for-woocommerce',
			'woocommerce-payments',
			'woocommerce-services',
		];

		const filteredExtensions = filterBusinessExtensions( extensions );

		expect( filteredExtensions ).toEqual( expectedExtensions );
	} );
} );
