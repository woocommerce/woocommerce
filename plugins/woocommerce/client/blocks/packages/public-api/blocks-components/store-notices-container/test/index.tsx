/**
 * External dependencies
 */
import { store as noticesStore } from '@wordpress/notices';
import { dispatch, select } from '@wordpress/data';
import { act, render, screen, waitFor, within } from '@testing-library/react';

/**
 * Internal dependencies
 */
import StoreNoticesContainer from '../index';

describe( 'StoreNoticesContainer', () => {
	it( 'Shows notices from the correct context', async () => {
		dispatch( noticesStore ).createErrorNotice( 'Custom test error', {
			id: 'custom-test-error',
			context: 'test-context',
		} );
		render( <StoreNoticesContainer context="test-context" /> );
		expect( screen.getAllByText( /Custom test error/i ) ).toHaveLength( 2 );
		// Clean up notices.
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'custom-test-error',
				'test-context'
			)
		);
		await waitFor( () => {
			return (
				select( noticesStore ).getNotices( 'test-context' ).length === 0
			);
		} );
	} );

	it( 'Does not show notices from other contexts', async () => {
		dispatch( noticesStore ).createErrorNotice( 'Custom test error 2', {
			id: 'custom-test-error-2',
			context: 'test-context',
		} );
		render( <StoreNoticesContainer context="other-context" /> );
		expect( screen.queryAllByText( /Custom test error 2/i ) ).toHaveLength(
			0
		);
		// Clean up notices.
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'custom-test-error-2',
				'test-context'
			)
		);
		await waitFor( () => {
			return (
				select( noticesStore ).getNotices( 'test-context' ).length === 0
			);
		} );
	} );

	it( 'Does not show snackbar notices', async () => {
		dispatch( noticesStore ).createErrorNotice( 'Custom test error 2', {
			id: 'custom-test-error-2',
			context: 'test-context',
			type: 'snackbar',
		} );
		render( <StoreNoticesContainer context="other-context" /> );
		expect( screen.queryAllByText( /Custom test error 2/i ) ).toHaveLength(
			0
		);
		// Clean up notices.
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'custom-test-error-2',
				'test-context'
			)
		);
		await waitFor( () => {
			return (
				select( noticesStore ).getNotices( 'test-context' ).length === 0
			);
		} );
	} );

	it( 'Shows additional notices', () => {
		render(
			<StoreNoticesContainer
				additionalNotices={ [
					{
						id: 'additional-test-error',
						status: 'error',
						spokenMessage: 'Additional test error',
						isDismissible: false,
						content: 'Additional test error',
						actions: [],
						speak: false,
						__unstableHTML: '',
						type: 'default',
					},
				] }
			/>
		);
		// Also counts the spokenMessage.
		expect( screen.getAllByText( /Additional test error/i ) ).toHaveLength(
			2
		);
	} );

	it( 'Shows notices from unregistered sub-contexts', async () => {
		dispatch( noticesStore ).createErrorNotice(
			'Custom first sub-context error',
			{
				id: 'custom-subcontext-test-error',
				context: 'wc/checkout/shipping-address',
			}
		);
		dispatch( noticesStore ).createErrorNotice(
			'Custom second sub-context error',
			{
				id: 'custom-subcontext-test-error',
				context: 'wc/checkout/billing-address',
			}
		);
		render( <StoreNoticesContainer context="wc/checkout" /> );
		// This should match against 2 messages, one for each sub-context.
		expect(
			screen.getAllByText( /Custom first sub-context error/i )
		).toHaveLength( 2 );
		expect(
			screen.getAllByText( /Custom second sub-context error/i )
		).toHaveLength( 2 );
		// Clean up notices.
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'custom-subcontext-test-error',
				'wc/checkout/shipping-address'
			)
		);
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'custom-subcontext-test-error',
				'wc/checkout/billing-address'
			)
		);
	} );

	it( 'Shows notices from several contexts', async () => {
		dispatch( noticesStore ).createErrorNotice( 'Custom shipping error', {
			id: 'custom-subcontext-test-error',
			context: 'wc/checkout/shipping-address',
		} );
		dispatch( noticesStore ).createErrorNotice( 'Custom billing error', {
			id: 'custom-subcontext-test-error',
			context: 'wc/checkout/billing-address',
		} );
		render(
			<StoreNoticesContainer
				context={ [
					'wc/checkout/billing-address',
					'wc/checkout/shipping-address',
				] }
			/>
		);
		// This should match against 4 elements; A written and spoken message for each error.
		expect( screen.getAllByText( /Custom shipping error/i ) ).toHaveLength(
			2
		);
		expect( screen.getAllByText( /Custom billing error/i ) ).toHaveLength(
			2
		);
		// Clean up notices.
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'custom-subcontext-test-error',
				'wc/checkout/shipping-address'
			)
		);
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'custom-subcontext-test-error',
				'wc/checkout/billing-address'
			)
		);
	} );

	it( 'Sanitizes HTML in a single dismissible notice before rendering', async () => {
		// The notice-creation pipeline (e.g. notify-errors.ts) decodes entities
		// before storing the message, so live HTML can reach the notice content.
		// The single-notice render branch must sanitize it via RawHTML rather than
		// injecting it as-is. See XSS regression.
		dispatch( noticesStore ).createErrorNotice(
			'PWNXSS <img src=x onerror="window.__xss=1">',
			{
				id: 'custom-xss-test-error',
				context: 'test-context',
			}
		);
		const { container } = render(
			<StoreNoticesContainer context="test-context" />
		);
		// The disallowed <img> must be stripped, so no image element is injected.
		expect( container.querySelector( 'img' ) ).toBeNull();
		expect( container.innerHTML ).not.toContain( 'onerror' );
		// The harmless text is still shown (visible + spoken message).
		expect( screen.getAllByText( /PWNXSS/i ).length ).toBeGreaterThan( 0 );
		// Clean up notices.
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'custom-xss-test-error',
				'test-context'
			)
		);
		await waitFor( () => {
			return (
				select( noticesStore ).getNotices( 'test-context' ).length === 0
			);
		} );
	} );

	it( 'Renders a single notice with the same list markup as multiple notices', async () => {
		dispatch( noticesStore ).createErrorNotice( 'Single list error', {
			id: 'single-list-error',
			context: 'test-context',
		} );
		const { container, rerender } = render(
			<StoreNoticesContainer context="test-context" />
		);
		const singleList = container.querySelectorAll(
			'.wc-block-components-notice-banner__list'
		);
		expect( singleList ).toHaveLength( 1 );
		expect( singleList[ 0 ].getAttribute( 'role' ) ).toBe( 'list' );
		expect( singleList[ 0 ].querySelectorAll( 'li' ) ).toHaveLength( 1 );
		expect(
			screen.getAllByText(
				/Please fix the following error before continuing/i
			).length
		).toBeGreaterThan( 0 );

		await act( () =>
			dispatch( noticesStore ).createErrorNotice( 'Second list error', {
				id: 'second-list-error',
				context: 'test-context',
			} )
		);
		rerender( <StoreNoticesContainer context="test-context" /> );

		const multipleList = container.querySelectorAll(
			'.wc-block-components-notice-banner__list'
		);
		expect( multipleList ).toHaveLength( 1 );
		expect( multipleList[ 0 ].querySelectorAll( 'li' ) ).toHaveLength( 2 );
		expect(
			screen.getAllByText(
				/Please fix the following errors before continuing/i
			).length
		).toBeGreaterThan( 0 );

		// Clean up notices.
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'single-list-error',
				'test-context'
			)
		);
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'second-list-error',
				'test-context'
			)
		);
	} );

	it( 'Exposes the notice id as data-id on each list item', async () => {
		// The classic notice templates put the notice id on the li via
		// wc_get_notice_data_attr(), and Store API errors use their error code as
		// the notice id. Keep the same hook here so scripts and themes can target
		// an individual notice.
		dispatch( noticesStore ).createErrorNotice( 'First data-id error', {
			id: 'woocommerce_rest_cart_coupon_error',
			context: 'test-context',
		} );
		dispatch( noticesStore ).createErrorNotice( 'Second data-id error', {
			id: 'woocommerce_rest_invalid_postcode',
			context: 'test-context',
		} );
		const { container } = render(
			<StoreNoticesContainer context="test-context" />
		);

		expect(
			[ ...container.querySelectorAll( 'li' ) ].map( ( item ) =>
				item.getAttribute( 'data-id' )
			)
		).toEqual( [
			'woocommerce_rest_cart_coupon_error',
			'woocommerce_rest_invalid_postcode',
		] );

		// Clean up notices.
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'woocommerce_rest_cart_coupon_error',
				'test-context'
			)
		);
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'woocommerce_rest_invalid_postcode',
				'test-context'
			)
		);
	} );

	it( 'Renders a non-dismissible notice with the same list markup, without a summary', async () => {
		dispatch( noticesStore ).createErrorNotice(
			'Non-dismissible list error',
			{
				id: 'non-dismissible-list-error',
				context: 'test-context',
				isDismissible: false,
			}
		);
		const { container } = render(
			<StoreNoticesContainer context="test-context" />
		);

		const lists = within( container ).getAllByRole( 'list' );
		expect( lists ).toHaveLength( 1 );
		expect( within( lists[ 0 ] ).getAllByRole( 'listitem' ) ).toHaveLength(
			1
		);
		expect( lists[ 0 ] ).toHaveClass(
			'wc-block-components-notice-banner__list'
		);
		// Each non-dismissible notice is its own banner, so it carries no summary.
		expect(
			container.querySelector(
				'.wc-block-components-notice-banner__summary'
			)
		).toBeNull();

		// Clean up notices.
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'non-dismissible-list-error',
				'test-context'
			)
		);
	} );

	it( 'Renders non-error notices with the same list markup, without a summary', async () => {
		dispatch( noticesStore ).createSuccessNotice( 'Coupon applied', {
			id: 'success-list-notice',
			context: 'test-context',
		} );
		const { container } = render(
			<StoreNoticesContainer context="test-context" />
		);

		const lists = within( container ).getAllByRole( 'list' );
		expect( lists ).toHaveLength( 1 );
		expect( within( lists[ 0 ] ).getAllByRole( 'listitem' ) ).toHaveLength(
			1
		);
		expect( lists[ 0 ] ).toHaveClass(
			'wc-block-components-notice-banner__list'
		);
		// The summary is only rendered for the error status.
		expect(
			container.querySelector(
				'.wc-block-components-notice-banner__summary'
			)
		).toBeNull();

		// Clean up notices.
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'success-list-notice',
				'test-context'
			)
		);
	} );

	it( 'Combine same notices from several contexts', async () => {
		dispatch( noticesStore ).createErrorNotice( 'Custom generic error', {
			id: 'custom-subcontext-test-error',
			context: 'wc/checkout/shipping-address',
		} );
		dispatch( noticesStore ).createErrorNotice( 'Custom generic error', {
			id: 'custom-subcontext-test-error',
			context: 'wc/checkout/billing-address',
		} );
		render(
			<StoreNoticesContainer
				context={ [
					'wc/checkout/billing-address',
					'wc/checkout/shipping-address',
				] }
			/>
		);
		// This should match against 2 elements; A written and spoken message.
		expect( screen.getAllByText( /Custom generic error/i ) ).toHaveLength(
			2
		);
		// Clean up notices.
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'custom-subcontext-test-error',
				'wc/checkout/shipping-address'
			)
		);
		await act( () =>
			dispatch( noticesStore ).removeNotice(
				'custom-subcontext-test-error',
				'wc/checkout/billing-address'
			)
		);
	} );
} );
