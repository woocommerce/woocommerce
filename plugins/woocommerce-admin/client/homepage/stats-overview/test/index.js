/**
 * External dependencies
 */
import { render, fireEvent, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { StatsOverview } from '../index';
import StatsList from '../stats-list';
import { recordEvent } from 'lib/tracks';

jest.mock( 'lib/tracks' );
// Mock the stats list so that it can be tested separately.
jest.mock( '../stats-list', () =>
	jest.fn().mockImplementation( () => <div>mocked stats list</div> )
);
// Mock the Install Jetpack CTA
jest.mock( '../install-jetpack-cta', () => {
	return jest
		.fn()
		.mockImplementation( () => <div>mocked install jetpack cta</div> );
} );

describe( 'StatsOverview tracking', () => {
	it( 'should record an event when a stat is toggled', () => {
		render(
			<StatsOverview
				userPrefs={ {
					hiddenStats: null,
				} }
				updateCurrentUserData={ () => {} }
			/>
		);

		const ellipsisBtn = screen.getByRole( 'button', {
			name: 'Choose which values to display',
		} );
		fireEvent.click( ellipsisBtn );
		const totalSalesBtn = screen.getByRole( 'menuitemcheckbox', {
			name: 'Total Sales',
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
} );

describe( 'StatsOverview toggle and persist stat preference', () => {
	it( 'should update preferences', () => {
		const updateCurrentUserData = jest.fn();

		render(
			<StatsOverview
				userPrefs={ {
					hiddenStats: null,
				} }
				updateCurrentUserData={ updateCurrentUserData }
			/>
		);

		const ellipsisBtn = screen.getByRole( 'button', {
			name: 'Choose which values to display',
		} );
		fireEvent.click( ellipsisBtn );
		const totalSalesBtn = screen.getByRole( 'menuitemcheckbox', {
			name: 'Total Sales',
		} );
		fireEvent.click( totalSalesBtn );

		expect( updateCurrentUserData ).toHaveBeenCalledWith( {
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

describe( 'StatsOverview rendering correct elements', () => {
	it( 'should include a link to all the overview page', () => {
		render(
			<StatsOverview
				userPrefs={ {
					hiddenStats: null,
				} }
				updateCurrentUserData={ () => {} }
			/>
		);

		const viewDetailedStatsLink = screen.getByText( 'View detailed stats' );
		expect( viewDetailedStatsLink ).toBeDefined();
		expect( viewDetailedStatsLink.href ).toBe(
			'http://localhost/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
		);
	} );
} );

describe( 'StatsOverview period selection', () => {
	it( 'should have Today selected by default', () => {
		render(
			<StatsOverview
				userPrefs={ {
					hiddenStats: null,
				} }
				updateCurrentUserData={ () => {} }
			/>
		);

		const todayBtn = screen.getByRole( 'tab', { name: 'Today' } );
		expect( todayBtn.classList ).toContain( 'is-active' );
	} );

	it( 'should select a new period', () => {
		render(
			<StatsOverview
				userPrefs={ {
					hiddenStats: null,
				} }
				updateCurrentUserData={ () => {} }
			/>
		);

		fireEvent.click( screen.getByRole( 'tab', { name: 'Month to date' } ) );

		// Check props handed down to StatsList have the right period
		expect( StatsList ).toHaveBeenLastCalledWith(
			{
				query: { compare: 'previous_period', period: 'month' },
				stats: [
					{
						chart: 'total_sales',
						label: 'Total Sales',
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
