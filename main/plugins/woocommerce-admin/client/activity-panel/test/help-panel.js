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
		it( 'only shows links for suggested payment gateways', () => {
			const fixtures = [
				{
					id: 'woocommerce_payments',
					text: 'WooPayments',
				},
				{
					id: 'stripe',
					text: 'Stripe',
				},
				{
					id: 'kco',
					text: 'Klarna',
				},
				{
					id: 'klarna_payments',
					text: 'Klarna',
				},
				{
					id: 'ppcp-gateway',
					text: 'PayPal Checkout',
				},
				{
					id: 'square_credit_card',
					text: 'Square',
				},
				{
					id: 'payfast',
					text: 'Payfast',
				},
				{
					id: 'eway',
					text: 'Eway',
				},
			];

			const noSuggestions = render(
				<HelpPanel
					paymentGatewaySuggestions={ () => [] }
					taskName="payments"
				/>
			);

			fixtures.forEach( ( method ) => {
				expect(
					noSuggestions.queryAllByText( ( text ) =>
						text.includes( method.text )
					)
				).toHaveLength( 0 );
			} );

			fixtures.forEach( ( method ) => {
				const { queryAllByText } = render(
					<HelpPanel
						paymentGatewaySuggestions={ { [ method.id ]: true } }
						taskName="payments"
					/>
				);

				expect(
					queryAllByText( ( text ) => text.includes( method.text ) )
						.length
				).toBeGreaterThanOrEqual( 1 );
			} );
		} );

		describe( 'only shows links for WooCommerce Tax (powered by WCS&T) when supported', () => {
			it( 'displays if no conflicting conditions are present', () => {
				const supportedCountry = render(
					<HelpPanel
						countryCode="US"
						taskLists={ [
							{
								id: 'setup',
								tasks: [
									{
										id: 'tax',
										additionalData: {
											woocommerceTaxCountries: [ 'US' ],
											taxJarActivated: false,
										},
									},
								],
							},
						] }
						taskName="tax"
					/>
				);

				expect(
					supportedCountry.getByText( /WooCommerce Tax/ )
				).toBeDefined();
			} );

			it( 'does not display if the TaxJar plugin is active', () => {
				const taxjarPluginEnabled = render(
					<HelpPanel
						countryCode="US"
						taskLists={ [
							{
								id: 'setup',
								tasks: [
									{
										id: 'tax',
										additionalData: {
											woocommerceTaxCountries: [ 'US' ],
											taxJarActivated: true,
										},
									},
								],
							},
						] }
						taskName="tax"
					/>
				);

				expect(
					taxjarPluginEnabled.queryByText( /WooCommerce Tax/ )
				).toBeNull();
			} );

			it( 'does not display if in an unsupported country', () => {
				const unSupportedCountry = render(
					<HelpPanel
						countryCode="NZ"
						taskLists={ [
							{
								id: 'setup',
								tasks: [
									{
										id: 'tax',
										additionalData: {
											woocommerceTaxCountries: [ 'US' ],
											taxJarActivated: false,
										},
									},
								],
							},
						] }
						taskName="tax"
					/>
				);

				expect(
					unSupportedCountry.queryByText( /WooCommerce Tax/ )
				).toBeNull();
			} );

			it( 'does not display if the WooCommerce Tax plugin is active', () => {
				const woocommerceTaxActivated = render(
					<HelpPanel
						countryCode="US"
						taskLists={ [
							{
								id: 'setup',
								tasks: [
									{
										id: 'tax',
										additionalData: {
											woocommerceTaxCountries: [ 'US' ],
											taxJarActivated: false,
											woocommerceTaxActivated: true,
										},
									},
								],
							},
						] }
						taskName="tax"
					/>
				);

				expect(
					woocommerceTaxActivated.queryByText( /WooCommerce Tax/ )
				).toBeNull();
			} );

			it( 'does not display if the WooCommerce Shipping plugin is active', () => {
				const woocommerceShippingActivated = render(
					<HelpPanel
						countryCode="US"
						taskLists={ [
							{
								id: 'setup',
								tasks: [
									{
										id: 'tax',
										additionalData: {
											woocommerceTaxCountries: [ 'US' ],
											taxJarActivated: false,
											woocommerceTaxActivated: false,
											woocommerceShippingActivated: true,
										},
									},
								],
							},
						] }
						taskName="tax"
					/>
				);

				expect(
					woocommerceShippingActivated.queryByText(
						/WooCommerce Tax/
					)
				).toBeNull();
			} );
		} );

		describe( 'only shows links for WooCommerce Shipping (powered by WCS&T) when supported', () => {
			it( 'displays if no conflicting conditions are present', () => {
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

			it( 'does not display if the WooCommerce Shipping & Tax plugin is active', () => {
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
			} );

			it( 'does not display if the WooCommerce Shipping plugin is active', () => {
				const wcsActive = render(
					<HelpPanel
						activePlugins={ [ 'woocommerce-shipping' ] }
						countryCode="US"
						taskName="shipping"
					/>
				);

				expect(
					wcsActive.queryByText( /WooCommerce Shipping/ )
				).toBeNull();
			} );

			it( 'does not display if the WooCommerce Tax plugin is active', () => {
				const wcsActive = render(
					<HelpPanel
						activePlugins={ [ 'woocommerce-tax' ] }
						countryCode="US"
						taskName="shipping"
					/>
				);

				expect(
					wcsActive.queryByText( /WooCommerce Shipping/ )
				).toBeNull();
			} );

			it( 'does not display if in an unsupported country', () => {
				const unSupportedCountry = render(
					<HelpPanel
						activePlugins={ [] }
						countryCode="UK"
						taskName="shipping"
					/>
				);

				expect(
					unSupportedCountry.queryByText( /WooCommerce Shipping/ )
				).toBeNull();
			} );
		} );

		it( 'only shows links for home screen when supported', () => {
			const homescreen = render(
				<HelpPanel
					activePlugins={ [ 'woocommerce-services' ] }
					taskName=""
				/>
			);

			const homescreenLinkTitles = [
				'Get Support',
				'Home Screen',
				'Inbox',
				'Stats Overview',
				'Store Management',
				'Store Setup Checklist',
			];

			homescreenLinkTitles.forEach( ( title ) => {
				expect( homescreen.getByText( title ) ).toBeDefined();
			} );
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
