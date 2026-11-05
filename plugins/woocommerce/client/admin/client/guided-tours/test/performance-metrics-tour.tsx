/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useUserPreferences } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { PerformanceMetricsTour } from '../performance-metrics-tour';

jest.mock( '@woocommerce/data', () => ( {
	...jest.requireActual( '@woocommerce/data' ),
	useUserPreferences: jest.fn(),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

type MockTourConfig = {
	steps: Array< { meta: { heading: string } } >;
	closeHandler: (
		steps: unknown[],
		currentStepIndex: number,
		source: string
	) => void;
	options?: { effects?: { autoScroll?: unknown } };
};

const mockRenderedConfigs: MockTourConfig[] = [];

jest.mock( '@woocommerce/components', () => ( {
	TourKit: ( { config }: { config: MockTourConfig } ) => {
		mockRenderedConfigs.push( config );
		return (
			<div>
				{ config.steps[ 0 ].meta.heading }
				<button
					onClick={ () =>
						config.closeHandler( config.steps, 0, 'done-btn' )
					}
				>
					Got it
				</button>
			</div>
		);
	},
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
		( recordEvent as jest.Mock ).mockClear();
		mockRenderedConfigs.length = 0;
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

	it( 'keeps the same scroll options across re-renders', () => {
		mockPreferences();

		const { rerender } = render(
			<PerformanceMetricsTour hasOpenedMenu={ false } />
		);
		rerender( <PerformanceMetricsTour hasOpenedMenu={ false } /> );

		// TourKit scrolls to the menu again whenever autoScroll changes.
		expect( mockRenderedConfigs ).toHaveLength( 2 );
		expect( mockRenderedConfigs[ 1 ].options?.effects?.autoScroll ).toBe(
			mockRenderedConfigs[ 0 ].options?.effects?.autoScroll
		);
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

	it( 'records a view once while the tour is shown', () => {
		mockPreferences();

		const { rerender } = render(
			<PerformanceMetricsTour hasOpenedMenu={ false } />
		);
		rerender( <PerformanceMetricsTour hasOpenedMenu={ false } /> );

		expect( recordEvent ).toHaveBeenCalledTimes( 1 );
		expect( recordEvent ).toHaveBeenCalledWith(
			'dash_indicators_tour_view'
		);
	} );

	it( 'does not record a view when the tour is not shown', () => {
		mockPreferences( 'yes' );
		render( <PerformanceMetricsTour hasOpenedMenu={ false } /> );

		mockPreferences( undefined, true );
		render( <PerformanceMetricsTour hasOpenedMenu={ false } /> );

		expect( recordEvent ).not.toHaveBeenCalled();
	} );

	it( 'records how the tour was dismissed', () => {
		mockPreferences();

		render( <PerformanceMetricsTour hasOpenedMenu={ false } /> );
		screen.getByRole( 'button', { name: 'Got it' } ).click();

		expect( recordEvent ).toHaveBeenCalledWith(
			'dash_indicators_tour_dismiss',
			{ source: 'done-btn' }
		);
	} );

	it( 'records a dismissal from the menu once', () => {
		mockPreferences();

		const { rerender } = render(
			<PerformanceMetricsTour hasOpenedMenu={ false } />
		);
		rerender( <PerformanceMetricsTour hasOpenedMenu /> );
		rerender( <PerformanceMetricsTour hasOpenedMenu /> );

		expect( recordEvent ).toHaveBeenCalledWith(
			'dash_indicators_tour_dismiss',
			{ source: 'menu' }
		);
		expect(
			( recordEvent as jest.Mock ).mock.calls.filter(
				( [ name ] ) => name === 'dash_indicators_tour_dismiss'
			)
		).toHaveLength( 1 );
	} );

	it( 'records nothing when the menu is opened after the tour was seen', () => {
		mockPreferences( 'yes' );

		render( <PerformanceMetricsTour hasOpenedMenu /> );

		expect( recordEvent ).not.toHaveBeenCalled();
	} );
} );
