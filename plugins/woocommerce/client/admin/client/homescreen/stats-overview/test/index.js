import { describe, expect, it, vi } from 'vitest';

/**
 * External dependencies
 */
import {
	render,
	fireEvent,
	screen,
	waitFor,
	act,
} from '@testing-library/react';
import { useUserPreferences } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { StatsOverview } from '../index';
import StatsList from '../stats-list';
vi.mock( '@woocommerce/tracks' );
// Mock the stats list so that it can be tested separately.
vi.mock( '../stats-list', () => {
	const mock = vi
		.fn()
		.mockImplementation( () => <div>mocked stats list</div> );
	return {
		default: mock,
		...mock,
	};
} );
// Mock the Install Jetpack CTA
vi.mock( '../install-jetpack-cta', () => {
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		InstallJetpackCTA: vi
			.fn()
			.mockImplementation( () => <div>mocked install jetpack cta</div> ),
	} );
} );
vi.mock( '@woocommerce/data', async () => {
	// Require the original module to not be mocked...
	const originalModule = await vi.importActual( '@woocommerce/data' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		__esModule: true,
		// Use it when dealing with esModules
		...originalModule,
		useUserPreferences: vi.fn(),
	} );
} );
vi.mock( '@wordpress/data', async () => {
	// Require the original module to not be mocked...
	const originalModule = await vi.importActual( '@wordpress/data' );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		__esModule: true,
		// Use it when dealing with esModules
		...originalModule,
		useSelect: vi.fn().mockReturnValue( {} ),
	} );
} );
vi.mock( '@woocommerce/data' );
describe( 'StatsOverview tracking', () => {
	it( 'should record an event when a stat is toggled', async () => {
		useUserPreferences.mockReturnValue( {
			updateUserPreferences: () => {},
			hiddenStats: null,
		} );
		await act( async () => {
			render( <StatsOverview /> );
		} );
		const ellipsisBtn = screen.getByRole( 'button', {
			name: 'Choose which values to display',
		} );
		await act( async () => {
			fireEvent.click( ellipsisBtn );
		} );
		const totalSalesBtn = screen.getByRole( 'menuitemcheckbox', {
			name: 'Total sales',
		} );
		await act( async () => {
			fireEvent.click( totalSalesBtn );
		} );
		expect( recordEvent ).toHaveBeenCalledWith(
			'statsoverview_indicators_toggle',
			{
				indicator_name: 'revenue/total_sales',
				status: 'off',
			}
		);
	} );
	it( 'should record an event when a period is clicked', async () => {
		useUserPreferences.mockReturnValue( {
			updateUserPreferences: () => {},
			hiddenStats: null,
		} );
		await act( async () => {
			render( <StatsOverview /> );
		} );
		const monthBtn = screen.getByRole( 'tab', {
			name: 'Month to date',
		} );
		await act( async () => {
			fireEvent.click( monthBtn );
		} );
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
		const updateUserPreferences = vi.fn();
		useUserPreferences.mockReturnValue( {
			updateUserPreferences,
			hiddenStats: null,
		} );
		await act( async () => {
			render( <StatsOverview /> );
		} );
		const ellipsisBtn = screen.getByRole( 'button', {
			name: 'Choose which values to display',
		} );
		await act( async () => {
			fireEvent.click( ellipsisBtn );
		} );
		const totalSalesBtn = screen.getByRole( 'menuitemcheckbox', {
			name: 'Total sales',
		} );
		await act( async () => {
			fireEvent.click( totalSalesBtn );
		} );
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
	it( 'should include a link to all the overview page', async () => {
		useUserPreferences.mockReturnValue( {
			updateUserPreferences: () => {},
			hiddenStats: null,
		} );
		await act( async () => {
			render( <StatsOverview /> );
		} );
		const viewDetailedStatsLink = screen.getByText( 'View detailed stats' );
		expect( viewDetailedStatsLink ).toBeDefined();
		expect( viewDetailedStatsLink.href ).toBe(
			'http://localhost/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview'
		);
	} );
} );
describe( 'StatsOverview period selection', () => {
	it( 'should have Today selected by default', async () => {
		useUserPreferences.mockReturnValue( {
			updateUserPreferences: () => {},
			hiddenStats: null,
		} );
		await act( async () => {
			render( <StatsOverview /> );
		} );
		const todayBtn = screen.getByRole( 'tab', {
			name: 'Today',
		} );
		expect( todayBtn.classList ).toContain( 'is-active' );
	} );
	it( 'should select a new period', async () => {
		useUserPreferences.mockReturnValue( {
			updateUserPreferences: () => {},
			hiddenStats: null,
		} );
		await act( async () => {
			render( <StatsOverview /> );
		} );
		await act( async () => {
			fireEvent.click(
				screen.getByRole( 'tab', {
					name: 'Month to date',
				} )
			);

			// Check props handed down to StatsList have the right period
		} ); // Check props handed down to StatsList have the right period
		expect( StatsList ).toHaveBeenLastCalledWith(
			{
				query: {
					compare: 'previous_period',
					period: 'month',
				},
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
