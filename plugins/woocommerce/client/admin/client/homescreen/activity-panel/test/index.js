import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import { render, screen, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useSelect } from '@wordpress/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { ActivityPanel } from '../';
vi.mock( '@wordpress/data', async () => {
	const originalModule = await vi.importActual( '@wordpress/data' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		__esModule: true,
		...originalModule,
		useSelect: vi.fn().mockReturnValue( {
			isTaskListHidden: false,
		} ),
	} );
} );

// Mock the panels.
vi.mock( '../panels', () => {
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		getAllPanels: vi.fn().mockImplementation( () => [
			{
				id: 'custom-panel-1',
				title: 'custom-panel-1',
				count: 10000,
				initialOpen: true,
				panel: <span>Custom panel 1</span>,
				collapsible: true,
			},
			{
				id: 'custom-panel-2',
				title: 'custom-panel-2',
				count: 20000,
				initialOpen: false,
				panel: <span>Custom panel 2</span>,
				collapsible: true,
			},
		] ),
	} );
} );

// Mock the order statuses.
vi.mock( '../orders/utils', () => {
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		getOrderStatuses: vi.fn().mockImplementation( () => [ 'status' ] ),
	} );
} );
describe( 'ActivityPanel', () => {
	it( 'should render a panel with two rows', () => {
		render( <ActivityPanel /> );
		expect( screen.getByText( 'custom-panel-1' ) ).not.toBeNull();
		expect( screen.getByText( 'custom-panel-2' ) ).not.toBeNull();
	} );
	it( 'should render one visible panel and one hidden panel', () => {
		render( <ActivityPanel /> );
		expect( screen.queryByText( 'Custom panel 1' ) ).toBeInTheDocument();
		expect(
			screen.queryByText( 'Custom panel 2' )
		).not.toBeInTheDocument();
	} );
	it( 'should render the count of unread items', () => {
		render( <ActivityPanel /> );
		expect( screen.queryByText( '10000' ) ).toBeInTheDocument();
		expect( screen.queryByText( '20000' ) ).toBeInTheDocument();
	} );
	it( 'should not render panels when loadingOrderAndProductCount is true', () => {
		useSelect.mockReturnValue( {
			isTaskListHidden: false,
			loadingOrderAndProductCount: true,
		} );
		render( <ActivityPanel /> );
		expect( screen.queryByText( 'custom-panel-1' ) ).toBeNull();
		expect( screen.queryByText( 'custom-panel-2' ) ).toBeNull();
	} );
	it( 'should record activity_panel_open Tracks event when panel is opened', async () => {
		useSelect.mockReturnValue( {
			isTaskListHidden: false,
		} );
		const { getByText } = render( <ActivityPanel /> );
		await act( async () => {
			userEvent.click( getByText( 'custom-panel-2' ) );
		} );
		expect( recordEvent ).toHaveBeenCalledWith( 'activity_panel_open', {
			tab: 'custom-panel-2',
		} );
	} );
} );
