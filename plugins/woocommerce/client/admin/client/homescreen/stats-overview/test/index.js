/**
 * External dependencies
 */
import { render, fireEvent, screen, waitFor } from '@testing-library/react';
import { useUserPreferences } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { StatsOverview } from '../index';
import StatsList from '../stats-list';

jest.mock( '@woocommerce/tracks' );
// Mock the stats list so that it can be tested separately.
jest.mock( '../stats-list', () =>
	jest.fn().mockImplementation( () => <div>mocked stats list</div> )
);
// Mock the Install Jetpack CTA
jest.mock( '../install-jetpack-cta', () => {
	return {
		InstallJetpackCTA: jest
			.fn()
			.mockImplementation( () => <div>mocked install jetpack cta</div> ),
	};
} );

jest.mock( '@woocommerce/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@woocommerce/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useUserPreferences: jest.fn(),
	};
} );

jest.mock( '@wordpress/data', () => {
	// Require the original module to not be mocked...
	const originalModule = jest.requireActual( '@wordpress/data' );

	return {
		__esModule: true, // Use it when dealing with esModules
		...originalModule,
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

jest.mock( '@woocommerce/data' );

describe( 'StatsOverview tracking', () => {
	it( 'should record an event when a stat is toggled', () => {
		useUserPreferences.mockReturnValue( {
			updateUserPreferences: () => {},
			hiddenStats: null,
		} );
		render( <StatsOverview /> );

		const ellipsisBtn = screen.getByRole( 'button', {
			name: 'Choose which values to display',
		} );
		fireEvent.click( ellipsisBtn );
		const totalSalesBtn = screen.getByRole( 'menuitemcheckbox', {
			name: 'Total sales',
		} );
		fireEvent.click( totalSalesBtn );

		expect( recordEvent ).toHaveBeenCalledWith(
			'statsoverview_indicators_toggle',
			{
				indicator_name: 'revenue/total_sales',
				status: 'off',
			}
		);
	} );

	it( 'should record an event when a period is clicked', () => {
		useUserPreferences.mockReturnValue( {
			updateUserPreferences: () => {},
			hiddenStats: null,
		} );
		render( <StatsOverview /> );

		const monthBtn = screen.getByRole( 'tab', {
			name: 'Month to date',
		} );
		fireEvent.click( monthBtn );

		expect( recordEvent ).toHaveBeenCalledWith(
			'statsoverview_date_picker_update',
			{
				period: 'month',
			}
		);
	} );
} );

describe( 'StatsOverview toggle and persist stat preference', () => {
	it( 'should update preferences', async () => {
		const updateUserPreferences = jest.fn();
		useUserPreferences.mockReturnValue( {
			updateUserPreferences,
			hiddenStats: null,
		} );

		render( <StatsOverview /> );

		const ellipsisBtn = screen.getByRole( 'button', {
			name: 'Choose which values to display',
		} );
		fireEvent.click( ellipsisBtn );
		const totalSalesBtn = screen.getByRole( 'menuitemcheckbox', {
			name: 'Total sales',
		} );
		fireEvent.click( totalSalesBtn );

		await waitFor( () => {
			expect( updateUserPreferences ).toHaveBeenCalledWith( {
				homepage_stats: {
					hiddenStats: [
						'revenue/net_revenue',
						'products/items_sold',
						'revenue/total_sales',
					],
				},
			} );
		} );
	} );
} );

describe( 'StatsOverview rendering correct elements', () => {
	it( 'should include a link to all the overview page', () => {
		useUserPreferences.mockReturnValue( {
			updateUserPreferences: () => {},
			hiddenStats: null,
		} );
		render( <StatsOverview /> );

		const viewDetailedStatsLink = screen.getByText( 'View detailed stats' );
		expect( viewDetailedStatsLink ).toBeDefined();
		expect( viewDetailedStatsLink.href ).toBe(
			'http://localhost/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
		);
	} );
} );

describe( 'StatsOverview period selection', () => {
	it( 'should have Today selected by default', () => {
		useUserPreferences.mockReturnValue( {
			updateUserPreferences: () => {},
			hiddenStats: null,
		} );
		render( <StatsOverview /> );

		const todayBtn = screen.getByRole( 'tab', { name: 'Today' } );
		expect( todayBtn.classList ).toContain( 'is-active' );
	} );

	it( 'should select a new period', () => {
		useUserPreferences.mockReturnValue( {
			updateUserPreferences: () => {},
			hiddenStats: null,
		} );
		render( <StatsOverview /> );

		fireEvent.click( screen.getByRole( 'tab', { name: 'Month to date' } ) );

		// Check props handed down to StatsList have the right period
		expect( StatsList ).toHaveBeenLastCalledWith(
			{
				query: { compare: 'previous_period', period: 'month' },
				stats: [
					{
						chart: 'total_sales',
						label: 'Total sales',
						stat: 'revenue/total_sales',
					},
					{
						chart: 'orders_count',
						label: 'Orders',
						stat: 'orders/orders_count',
					},
				],
			},
			{}
		);
	} );
} );
