/**
 * Internal dependencies
 */
import { filterBusinessExtensions } from '../flows/selective-bundle';
import { createInitialValues } from '../flows/selective-bundle/selective-extensions-bundle';

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

	describe( 'createInitialValues', () => {
		test( 'selected by default', () => {
			const extensions = [
				{
					plugins: [
						{
							key: 'visible-and-not-selected',
							selected: false,
							isVisible: () => true,
						},
						{
							key: 'visible-and-selected',
							selected: true,
							isVisible: () => true,
						},
						{
							key: 'this-should-not-show-at-all',
							selected: true,
							isVisible: () => false,
						},
					],
				},
			];

			const values = createInitialValues( extensions, 'US', '', [] );

			expect( values ).toEqual(
				expect.objectContaining( {
					'visible-and-not-selected': false,
					'visible-and-selected': true,
				} )
			);

			expect( values ).not.toContain( 'this-should-not-show-at-all' );
		} );
	} );
} );
