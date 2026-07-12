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
			mockCreateElement(
				'span',
				null,
				onSubmitLabel ?? 'Default submit label'
			),
	};
} );

describe( 'CustomerEffortScoreTracksContainer', () => {
	let hadPagenow;
	let hadAdminpage;
	let originalPagenow;
	let originalAdminpage;

	beforeEach( () => {
		hadPagenow = Object.prototype.hasOwnProperty.call( window, 'pagenow' );
		hadAdminpage = Object.prototype.hasOwnProperty.call(
			window,
			'adminpage'
		);
		originalPagenow = window.pagenow;
		originalAdminpage = window.adminpage;
		window.pagenow = 'product';
		window.adminpage = 'post-php';
	} );

	afterEach( () => {
		if ( hadPagenow ) {
			window.pagenow = originalPagenow;
		} else {
			delete window.pagenow;
		}

		if ( hadAdminpage ) {
			window.adminpage = originalAdminpage;
		} else {
			delete window.adminpage;
		}
	} );

	it( 'forwards the canonical submit label for a matching queue item', () => {
		const clearQueue = jest.fn();

		render(
			<CustomerEffortScoreTracksContainer
				queue={ [
					{
						onSubmitLabel: 'Canonical success',
						pagenow: 'product',
						adminpage: 'post-php',
					},
				] }
				resolving={ false }
				clearQueue={ clearQueue }
			/>
		);

		expect( screen.getByText( 'Canonical success' ) ).toBeInTheDocument();
		expect( clearQueue ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'prefers the canonical submit label on a normalized queue item', () => {
		const clearQueue = jest.fn();

		render(
			<CustomerEffortScoreTracksContainer
				queue={ [
					{
						onSubmitLabel: 'Canonical success',
						onsubmit_label: 'Legacy value',
						pagenow: 'product',
						adminpage: 'post-php',
					},
				] }
				resolving={ false }
				clearQueue={ clearQueue }
			/>
		);

		expect( screen.getByText( 'Canonical success' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Legacy value' ) ).not.toBeInTheDocument();
		expect( clearQueue ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'ignores queue items for a different page', () => {
		const clearQueue = jest.fn();

		render(
			<CustomerEffortScoreTracksContainer
				queue={ [
					{
						onSubmitLabel: 'Different page',
						pagenow: 'edit-product',
						adminpage: 'edit-php',
					},
				] }
				resolving={ false }
				clearQueue={ clearQueue }
			/>
		);

		expect(
			screen.queryByText( 'Different page' )
		).not.toBeInTheDocument();
		expect( clearQueue ).not.toHaveBeenCalled();
	} );
} );
