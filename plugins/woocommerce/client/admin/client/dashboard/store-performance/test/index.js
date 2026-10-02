/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import StorePerformance from '../index';

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	withSelect: ( mapSelect ) => ( Component ) => ( props ) => (
		<Component { ...props } { ...mapSelect( jest.fn(), props ) } />
	),
} ) );

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
	PerformanceMetricsTour: ( { hasOpenedMenu } ) => (
		<div>{ hasOpenedMenu ? 'Menu opened' : 'Menu not opened' }</div>
	),
} ) );

describe( 'StorePerformance tour', () => {
	it( 'tells the tour when the menu is opened', () => {
		render( <StorePerformance hiddenBlocks={ [] } query={ {} } /> );

		expect( screen.getByText( 'Menu not opened' ) ).toBeInTheDocument();

		fireEvent.click( screen.getByRole( 'button', { name: 'Open menu' } ) );

		expect( screen.getByText( 'Menu opened' ) ).toBeInTheDocument();
	} );
} );
