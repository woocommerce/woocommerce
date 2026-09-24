/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useUserPreferences } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { PerformanceMetricsTour } from '../performance-metrics-tour';

jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useUserPreferences: jest.fn(),
} ) );

jest.mock( '@woocommerce/components', () => ( {
	TourKit: ( {
		config,
	}: {
		config: {
			steps: Array< { meta: { heading: string } } >;
			closeHandler: () => void;
		};
	} ) => (
		<div>
			{ config.steps[ 0 ].meta.heading }
			<button onClick={ config.closeHandler }>Got it</button>
		</div>
	),
} ) );

const updateUserPreferences = jest.fn();

const mockPreferences = ( hasShownTour?: string, isRequesting = false ) => {
	( useUserPreferences as jest.Mock ).mockReturnValue( {
		updateUserPreferences,
		isRequesting,
		dashboard_performance_tour_shown: hasShownTour,
	} );
};

describe( 'PerformanceMetricsTour', () => {
	beforeEach( () => {
		updateUserPreferences.mockClear();
	} );

	it( 'shows the tour when the user has not seen it', () => {
		mockPreferences();

		render( <PerformanceMetricsTour hasOpenedMenu={ false } /> );

		expect(
			screen.getByText( 'Choose which metrics to display' )
		).toBeInTheDocument();
	} );

	it( 'does not show the tour once the user has seen it', () => {
		mockPreferences( 'yes' );

		const { container } = render(
			<PerformanceMetricsTour hasOpenedMenu={ false } />
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'does not show the tour while the user preferences are loading', () => {
		mockPreferences( undefined, true );

		const { container } = render(
			<PerformanceMetricsTour hasOpenedMenu={ false } />
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'remembers the tour for the user when it is closed', () => {
		mockPreferences();

		render( <PerformanceMetricsTour hasOpenedMenu={ false } /> );
		screen.getByRole( 'button', { name: 'Got it' } ).click();

		expect( updateUserPreferences ).toHaveBeenCalledWith( {
			dashboard_performance_tour_shown: 'yes',
		} );
	} );

	it( 'hides and remembers the tour once when the menu is opened', () => {
		mockPreferences();

		const { container, rerender } = render(
			<PerformanceMetricsTour hasOpenedMenu={ false } />
		);
		rerender( <PerformanceMetricsTour hasOpenedMenu /> );
		rerender( <PerformanceMetricsTour hasOpenedMenu /> );

		expect( container ).toBeEmptyDOMElement();
		expect( updateUserPreferences ).toHaveBeenCalledTimes( 1 );
		expect( updateUserPreferences ).toHaveBeenCalledWith( {
			dashboard_performance_tour_shown: 'yes',
		} );
	} );

	it( 'does not save again when the menu is opened after the tour was seen', () => {
		mockPreferences( 'yes' );

		render( <PerformanceMetricsTour hasOpenedMenu /> );

		expect( updateUserPreferences ).not.toHaveBeenCalled();
	} );
} );
