/**
 * Internal dependencies
 */
import {
	filterBusinessExtensions,
	prepareExtensionTrackingData,
} from '../flows/selective-bundle';
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

	describe( 'prepareExtensionTrackingData', () => {
		test( 'preparing extensions for tracking', () => {
			const extensions = {
				'creative-mail-by-constant-contact': true,
				'facebook-for-woocommerce': false,
				install_extensions: true,
				jetpack: false,
				'google-listings-and-ads': true,
				'mailchimp-for-woocommerce': false,
				'woocommerce-payments': true,
			};

			const expectedExtensions = {
				install_creative_mail_by_constant_contact: true,
				install_facebook_for_woocommerce: false,
				install_jetpack: false,
				install_google_listings_and_ads: true,
				install_mailchimp_for_woocommerce: false,
				install_wcpay: true,
			};

			const installedExtensions = prepareExtensionTrackingData(
				extensions
			);

			expect( installedExtensions ).toEqual( expectedExtensions );
		} );
		test( 'preparing shipping and tax extensions for tracking', () => {
			const extensions = {
				'woocommerce-services:shipping': true,
				'woocommerce-services:tax': true,
			};

			const expectedExtensions = {
				install_woocommerce_services: true,
			};

			expect( prepareExtensionTrackingData( extensions ) ).toEqual(
				expectedExtensions
			);

			extensions[ 'woocommerce-services:shipping' ] = false;
			extensions[ 'woocommerce-services:tax' ] = true;

			expect( prepareExtensionTrackingData( extensions ) ).toEqual(
				expectedExtensions
			);

			extensions[ 'woocommerce-services:shipping' ] = true;
			extensions[ 'woocommerce-services:tax' ] = false;

			expect( prepareExtensionTrackingData( extensions ) ).toEqual(
				expectedExtensions
			);

			extensions[ 'woocommerce-services:shipping' ] = false;
			extensions[ 'woocommerce-services:tax' ] = false;
			expectedExtensions.install_woocommerce_services = false;

			expect( prepareExtensionTrackingData( extensions ) ).toEqual(
				expectedExtensions
			);
		} );
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
