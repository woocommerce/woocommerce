import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CustomerEffortScoreTracksContainer } from '..';
vi.mock( '@wordpress/compose', () => {
	const mock = {
		compose: () => ( Component ) => Component,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@wordpress/data', () => {
	const mock = {
		withDispatch: vi.fn(),
		withSelect: vi.fn(),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/data', () => {
	const mock = {
		optionsStore: 'wc/admin/options',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../../../store', () => {
	const mock = {
		QUEUE_OPTION_NAME: 'woocommerce_ces_tracks_queue',
		STORE_KEY: 'wc/customer-effort-score',
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '../..', async () => {
	const { createElement: mockCreateElement } =
		await vi.importActual( '@wordpress/element' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		CustomerEffortScoreTracks: ( { onSubmitLabel } ) =>
			mockCreateElement( 'span', null, onSubmitLabel ),
	} );
} );
describe( 'CustomerEffortScoreTracksContainer', () => {
	const originalPagenow = window.pagenow;
	const originalAdminpage = window.adminpage;
	beforeEach( () => {
		window.pagenow = 'product';
		window.adminpage = 'post-php';
	} );
	afterEach( () => {
		window.pagenow = originalPagenow;
		window.adminpage = originalAdminpage;
	} );
	it( 'forwards the canonical label from a normalized queue item', () => {
		const clearQueue = vi.fn();
		render(
			createElement( CustomerEffortScoreTracksContainer, {
				queue: [
					{
						onSubmitLabel: 'Canonical success',
						onsubmit_label: 'Legacy value',
						pagenow: 'product',
						adminpage: 'post-php',
					},
				],
				resolving: false,
				clearQueue,
			} )
		);
		expect( screen.getByText( 'Canonical success' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Legacy value' ) ).not.toBeInTheDocument();
		expect( clearQueue ).toHaveBeenCalledTimes( 1 );
	} );
} );
