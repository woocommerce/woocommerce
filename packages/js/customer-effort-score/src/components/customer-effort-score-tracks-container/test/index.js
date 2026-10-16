/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { CustomerEffortScoreTracksContainer } from '..';

jest.mock( '@wordpress/compose', () => ( {
	compose: () => ( Component ) => Component,
} ) );

jest.mock( '@wordpress/data', () => ( {
	withDispatch: jest.fn(),
	withSelect: jest.fn(),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	optionsStore: 'wc/admin/options',
} ) );

jest.mock( '../../../store', () => ( {
	QUEUE_OPTION_NAME: 'woocommerce_ces_tracks_queue',
	STORE_KEY: 'wc/customer-effort-score',
} ) );

jest.mock( '../..', () => {
	const { createElement: mockCreateElement } =
		jest.requireActual( '@wordpress/element' );

	return {
		CustomerEffortScoreTracks: ( { onSubmitLabel } ) =>
			mockCreateElement( 'span', null, onSubmitLabel ),
	};
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
		const clearQueue = jest.fn();

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
