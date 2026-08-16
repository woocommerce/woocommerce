/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { updateQueryString } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ReportFilters from '../';

jest.mock( '@woocommerce/navigation', () => ( {
	...jest.requireActual( '@woocommerce/navigation' ),
	updateQueryString: jest.fn(),
} ) );

jest.mock( '../../advanced-filters', () => () => null );
jest.mock( '../../compare-filter', () => ( {
	CompareFilter: () => null,
} ) );
jest.mock( '../../filter-picker', () => () => null );

const path = '/analytics/revenue';
const query = {
	period: 'today',
	compare: 'previous_year',
	filter: 'all',
};

const dateQuery = {
	period: 'today',
	compare: 'previous_year',
	after: null,
	before: null,
	primaryDate: {
		label: 'Today',
		range: 'February 15, 2022',
	},
	secondaryDate: {
		label: 'Previous year',
		range: 'February 15, 2021',
	},
};

const renderFilters = ( onDateSelect ) =>
	render(
		<ReportFilters
			dateQuery={ dateQuery }
			filters={ [] }
			isoDateFormat="YYYY-MM-DD"
			onDateSelect={ onDateSelect }
			path={ path }
			query={ query }
		/>
	);

const openDatePicker = async () => {
	await userEvent.click(
		screen.getByRole( 'button', {
			name: /Today \(February 15, 2022\).*Previous year/,
		} )
	);
};

describe( 'ReportFilters date selection', () => {
	beforeEach( () => {
		jest.useFakeTimers();
		jest.setSystemTime( new Date( '2022-02-15T12:00:00Z' ) );
		jest.clearAllMocks();
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	it( 'maps the Last month preset to navigation and the selection callback', async () => {
		const onDateSelect = jest.fn();
		renderFilters( onDateSelect );
		await openDatePicker();

		await userEvent.click(
			screen.getByRole( 'radio', { name: 'Last month' } )
		);
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Update' } )
		);

		const expectedSelection = {
			period: 'last_month',
			compare: 'previous_year',
			after: undefined,
			before: undefined,
		};
		expect( updateQueryString ).toHaveBeenCalledTimes( 1 );
		expect( updateQueryString ).toHaveBeenCalledWith(
			expectedSelection,
			path,
			query
		);
		expect( onDateSelect ).toHaveBeenCalledTimes( 1 );
		expect( onDateSelect ).toHaveBeenCalledWith( expectedSelection );
	} );

	it( 'maps a custom date range to navigation and the selection callback', async () => {
		const onDateSelect = jest.fn();
		renderFilters( onDateSelect );
		await openDatePicker();

		await userEvent.click( screen.getByRole( 'tab', { name: 'Custom' } ) );
		await userEvent.type(
			screen.getByRole( 'textbox', { name: 'Start Date' } ),
			'01/01/2022'
		);
		await userEvent.type(
			screen.getByRole( 'textbox', { name: 'End Date' } ),
			'01/30/2022'
		);
		await userEvent.click(
			screen.getByRole( 'button', { name: 'Update' } )
		);

		const expectedSelection = {
			period: 'custom',
			compare: 'previous_year',
			after: '2022-01-01',
			before: '2022-01-30',
		};
		expect( updateQueryString ).toHaveBeenCalledTimes( 1 );
		expect( updateQueryString ).toHaveBeenCalledWith(
			expectedSelection,
			path,
			query
		);
		expect( onDateSelect ).toHaveBeenCalledTimes( 1 );
		expect( onDateSelect ).toHaveBeenCalledWith( expectedSelection );
	} );
} );
