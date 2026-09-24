/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';
import { dispatch, select } from '@wordpress/data';
import { optionsStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import StorePerformance from '../index';

const mockUpdateOptions = jest.fn();
let mockTourOptionValue = false;

jest.mock( '@wordpress/data', () => {
	const actual = jest.requireActual( '@wordpress/data' );

	return {
		...actual,
		withSelect: ( mapSelect ) => ( Component ) => ( props ) => (
			<Component { ...props } { ...mapSelect( jest.fn(), props ) } />
		),
		// Other packages dispatch to their own stores on load, so only the
		// options store is replaced.
		select: jest.fn( actual.select ),
		dispatch: jest.fn( actual.dispatch ),
	};
} );

jest.mock( '@woocommerce/components', () => ( {
	...jest.requireActual( '@woocommerce/components' ),
	SectionHeader: ( { title, menu } ) => (
		<div>
			{ title }
			{ menu }
		</div>
	),
	EllipsisMenu: ( { onToggle } ) => (
		<button onClick={ onToggle }>Open menu</button>
	),
} ) );

jest.mock( '~/utils/admin-settings', () => ( {
	getAdminSetting: () => ( { performanceIndicators: [] } ),
} ) );

jest.mock( '~/guided-tours/performance-metrics-tour', () => ( {
	PERFORMANCE_TOUR_OPTION: 'woocommerce_analytics_performance_tour_shown',
	PerformanceMetricsTour: ( { onDismiss } ) => (
		<div>
			Performance tour
			<button onClick={ onDismiss }>Got it</button>
		</div>
	),
} ) );

const renderStorePerformance = () =>
	render( <StorePerformance hiddenBlocks={ [] } query={ {} } /> );

describe( 'StorePerformance tour', () => {
	beforeEach( () => {
		mockUpdateOptions.mockClear();
		mockTourOptionValue = false;
		const actual = jest.requireActual( '@wordpress/data' );
		select.mockImplementation( ( store ) =>
			store === optionsStore
				? { getOption: () => mockTourOptionValue }
				: actual.select( store )
		);
		dispatch.mockImplementation( ( store ) =>
			store === optionsStore
				? { updateOptions: mockUpdateOptions }
				: actual.dispatch( store )
		);
	} );

	it( 'hides the tour and remembers it when the menu is opened', () => {
		renderStorePerformance();

		fireEvent.click( screen.getByRole( 'button', { name: 'Open menu' } ) );

		expect( screen.queryByText( 'Performance tour' ) ).toBeNull();
		expect( mockUpdateOptions ).toHaveBeenCalledWith( {
			woocommerce_analytics_performance_tour_shown: 'yes',
		} );
	} );

	it( 'remembers the tour only once when the menu is opened again', () => {
		renderStorePerformance();

		fireEvent.click( screen.getByRole( 'button', { name: 'Open menu' } ) );
		fireEvent.click( screen.getByRole( 'button', { name: 'Open menu' } ) );

		expect( mockUpdateOptions ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not save the option again when the tour was already seen', () => {
		mockTourOptionValue = 'yes';
		renderStorePerformance();

		fireEvent.click( screen.getByRole( 'button', { name: 'Open menu' } ) );

		expect( mockUpdateOptions ).not.toHaveBeenCalled();
	} );

	it( 'hides the tour and remembers it when the tour is closed', () => {
		renderStorePerformance();

		fireEvent.click( screen.getByRole( 'button', { name: 'Got it' } ) );

		expect( screen.queryByText( 'Performance tour' ) ).toBeNull();
		expect( mockUpdateOptions ).toHaveBeenCalledTimes( 1 );
	} );
} );
