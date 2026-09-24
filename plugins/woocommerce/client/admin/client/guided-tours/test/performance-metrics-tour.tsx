/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	PERFORMANCE_TOUR_OPTION,
	PerformanceMetricsTour,
} from '../performance-metrics-tour';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
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

const mockOptionState = ( value: unknown, isResolved = true ) => {
	( useSelect as jest.Mock ).mockImplementation( ( mapSelect ) =>
		mapSelect( () => ( {
			getOption: ( name: string ) =>
				name === PERFORMANCE_TOUR_OPTION ? value : undefined,
			hasFinishedResolution: () => isResolved,
		} ) )
	);
};

describe( 'PerformanceMetricsTour', () => {
	it( 'shows the tour when it has not been seen', () => {
		mockOptionState( false );

		render( <PerformanceMetricsTour onDismiss={ jest.fn() } /> );

		expect(
			screen.getByText( 'Choose which metrics to display' )
		).toBeInTheDocument();
	} );

	it( 'does not show the tour once it has been seen', () => {
		mockOptionState( 'yes' );

		const { container } = render(
			<PerformanceMetricsTour onDismiss={ jest.fn() } />
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'does not show the tour while the option is loading', () => {
		mockOptionState( undefined, false );

		const { container } = render(
			<PerformanceMetricsTour onDismiss={ jest.fn() } />
		);

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'calls onDismiss when the tour is closed', () => {
		mockOptionState( false );
		const onDismiss = jest.fn();

		render( <PerformanceMetricsTour onDismiss={ onDismiss } /> );
		screen.getByRole( 'button', { name: 'Got it' } ).click();

		expect( onDismiss ).toHaveBeenCalledTimes( 1 );
	} );
} );
