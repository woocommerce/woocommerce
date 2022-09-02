/**
 * Internal dependencies
 */
import {
	filterBusinessExtensions,
	isSellingElsewhere,
	isSellingOtherPlatformInPerson,
	prepareExtensionTrackingData,
	prepareExtensionTrackingInstallationData,
} from '../flows/selective-bundle';
import { createInstallExtensionOptions } from '../flows/selective-bundle/selective-extensions-bundle';

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
				'woocommerce-payments:us': true,
			};

			const expectedExtensions = {
				install_creative_mail_by_constant_contact: true,
				install_facebook_for_woocommerce: false,
				install_jetpack: false,
				install_google_listings_and_ads: true,
				install_mailchimp_for_woocommerce: false,
				install_wcpay: true,
			};

			const installedExtensions =
				prepareExtensionTrackingData( extensions );

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

	describe( 'extension installation tracking', () => {
		const extensions = {
			'creative-mail-by-constant-contact': true,
			'facebook-for-woocommerce': false,
			install_extensions: true,
			jetpack: false,
			'google-listings-and-ads': true,
			'mailchimp-for-woocommerce': false,
			'woocommerce-payments:us': true,
		};

		it( 'should return a list of installed and activated extensions', () => {
			const data = prepareExtensionTrackingInstallationData( extensions, {
				data: {
					activated: [
						'woocommerce-payments',
						'google-listings-and-ads',
					],
					install_time: {
						'woocommerce-payments': 2000,
					},
				},
			} );
			expect( data.installed_extensions ).toEqual( [ 'wcpay' ] );
			expect( data.activated_extensions ).toEqual( [
				'woocommerce-payments',
				'google-listings-and-ads',
			] );
		} );

		it( 'should add install_time_extension_name prop if install_time is set with correct timebox', () => {
			const data = prepareExtensionTrackingInstallationData( extensions, {
				data: {
					activated: [
						'woocommerce-payments',
						'google-listings-and-ads',
					],
					install_time: {
						'woocommerce-payments': 200,
						'google-listings-and-ads': 12023,
					},
				},
			} );
			expect( data.install_time_wcpay ).toEqual( '0-2s' );
			expect( data.install_time_google_listings_and_ads ).toEqual(
				'10-15s'
			);
		} );
	} );

	describe( 'createInstallExtensionOptions', () => {
		test( 'selected by default', () => {
			const installableExtensions = [
				{
					plugins: [
						{
							key: 'visible-and-not-selected',
						},
						{
							key: 'visible-and-selected',
						},
					],
				},
			];

			const values = createInstallExtensionOptions(
				installableExtensions,
				{ install_extensions: true }
			);

			expect( values ).toEqual(
				expect.objectContaining( {
					install_extensions: true,
					'visible-and-not-selected': true,
					'visible-and-selected': true,
				} )
			);
		} );
	} );

	describe( 'Currently selling elsewhere', () => {
		test( 'isSellingElsewhere', () => {
			const sellingElsewhere = isSellingElsewhere( 'other' );
			const notSellingElsewhere = isSellingElsewhere( 'no' );

			expect( sellingElsewhere ).toBeTruthy();
			expect( notSellingElsewhere ).toBeFalsy();
		} );
		test( 'isSellingOtherPlatformInPerson', () => {
			const sellingAnotherPlatformAndInPerson =
				isSellingOtherPlatformInPerson( 'brick-mortar-other' );
			const notSellingAnotherPlatformAndInPerson =
				isSellingOtherPlatformInPerson( 'no' );

			expect( sellingAnotherPlatformAndInPerson ).toBeTruthy();
			expect( notSellingAnotherPlatformAndInPerson ).toBeFalsy();
		} );
	} );
} );
