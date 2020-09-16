/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { addFilter, removeAllFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { HelpPanel, SETUP_TASK_HELP_ITEMS_FILTER } from '../panels/help';

describe( 'Activity Panels', () => {
	describe( 'Help', () => {
		it( 'only shows links for available payment methods', () => {
			const fixtures = [
				{
					key: 'wcpay',
					text: 'WooCommerce Payments',
				},
				{
					key: 'stripe',
					text: 'Stripe',
				},
				{
					key: 'klarna_checkout',
					text: 'Klarna',
				},
				{
					key: 'klarna_payments',
					text: 'Klarna',
				},
				{
					key: 'paypal',
					text: 'PayPal Checkout',
				},
				{
					key: 'square',
					text: 'Square',
				},
				{
					key: 'payfast',
					text: 'PayFast',
				},
				{
					key: 'eway',
					text: 'eWAY',
				},
			];

			const noMethods = render(
				<HelpPanel getPaymentMethods={ () => [] } taskName="payments" />
			);

			fixtures.forEach( ( method ) => {
				expect(
					noMethods.queryAllByText( ( text ) =>
						text.includes( method.text )
					)
				).toHaveLength( 0 );
			} );

			fixtures.forEach( ( method ) => {
				const { queryAllByText } = render(
					<HelpPanel
						getPaymentMethods={ () => [ method ] }
						taskName="payments"
					/>
				);

				expect(
					queryAllByText( ( text ) => text.includes( method.text ) )
						.length
				).toBeGreaterThanOrEqual( 1 );
			} );
		} );

		it( 'only shows links for automated tax when supported', () => {
			const taxjarPluginEnabled = render(
				<HelpPanel
					countryCode="US"
					getSetting={ () => ( {
						automatedTaxSupportedCountries: [ 'US' ],
						taxJarActivated: true,
					} ) }
					taskName="tax"
				/>
			);

			expect(
				taxjarPluginEnabled.queryByText( /WooCommerce Tax/ )
			).toBeNull();

			const unSupportedCountry = render(
				<HelpPanel
					countryCode="NZ"
					getSetting={ () => ( {
						automatedTaxSupportedCountries: [ 'US' ],
						taxJarActivated: false,
					} ) }
					taskName="tax"
				/>
			);

			expect(
				unSupportedCountry.queryByText( /WooCommerce Tax/ )
			).toBeNull();

			const supportedCountry = render(
				<HelpPanel
					countryCode="US"
					getSetting={ () => ( {
						automatedTaxSupportedCountries: [ 'US' ],
						taxJarActivated: false,
					} ) }
					taskName="tax"
				/>
			);

			expect(
				supportedCountry.getByText( /WooCommerce Tax/ )
			).toBeDefined();
		} );

		it( 'only shows links for WooCommerce Shipping when supported', () => {
			const wcsActive = render(
				<HelpPanel
					activePlugins={ [ 'woocommerce-services' ] }
					countryCode="US"
					taskName="shipping"
				/>
			);

			expect(
				wcsActive.queryByText( /WooCommerce Shipping/ )
			).toBeNull();

			const unSupportedCountry = render(
				<HelpPanel
					activePlugins={ [ 'woocommerce-services' ] }
					countryCode="UK"
					taskName="shipping"
				/>
			);

			expect(
				unSupportedCountry.queryByText( /WooCommerce Shipping/ )
			).toBeNull();

			const supportedCountry = render(
				<HelpPanel
					activePlugins={ [] }
					countryCode="US"
					taskName="shipping"
				/>
			);

			expect(
				supportedCountry.getByText( /WooCommerce Shipping/ )
			).toBeDefined();
		} );

		describe( 'Filters', () => {
			const testNamespace = 'wc/admin/tests';

			afterEach( () => {
				removeAllFilters( SETUP_TASK_HELP_ITEMS_FILTER, testNamespace );
			} );

			it( 'defaults to generic link with non-arrays', () => {
				// Return a non-array.
				addFilter(
					SETUP_TASK_HELP_ITEMS_FILTER,
					testNamespace,
					() => ( {} )
				);

				const nonArray = render( <HelpPanel taskName="appearance" /> );

				// Verify non-arrays default to generic docs link.
				expect(
					nonArray.getByText( 'WooCommerce Docs' )
				).toBeDefined();
			} );

			it( 'defaults to generic link with empty arrays', () => {
				// Return an empty array.
				addFilter(
					SETUP_TASK_HELP_ITEMS_FILTER,
					testNamespace,
					() => []
				);

				const emptyArray = render(
					<HelpPanel taskName="appearance" />
				);

				// Verify empty arrays default to generic docs link.
				expect(
					emptyArray.getByText( 'WooCommerce Docs' )
				).toBeDefined();
			} );

			it( 'defaults to generic link with malformed arrays', () => {
				// Return an malformed array.
				addFilter( SETUP_TASK_HELP_ITEMS_FILTER, testNamespace, () => [
					{
						title: 'missing a link!',
					},
				] );

				const badArray = render( <HelpPanel taskName="appearance" /> );

				// Verify malformed arrays default to generic docs link.
				expect(
					badArray.getByText( 'WooCommerce Docs' )
				).toBeDefined();
			} );

			it( 'allows help items to be replaced', () => {
				// Replace all help items.
				addFilter( SETUP_TASK_HELP_ITEMS_FILTER, testNamespace, () => [
					{
						title: 'There can only be one',
						link: 'https://www.google.com/search?q=highlander',
					},
				] );

				const replacedArray = render(
					<HelpPanel taskName="appearance" />
				);

				// Verify filtered array.
				expect(
					replacedArray.queryByText( 'WooCommerce Docs' )
				).toBeNull();
				expect(
					replacedArray.getByText( 'There can only be one' )
				).toBeDefined();
			} );
		} );
	} );
} );
