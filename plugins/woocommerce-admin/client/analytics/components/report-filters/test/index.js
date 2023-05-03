/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { recordEvent } from '@woocommerce/tracks';

import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ReportFilters from '..';

jest.mock( '@woocommerce/tracks', () => ( { recordEvent: jest.fn() } ) );

describe( 'ReportFilters', () => {
	test( 'should fire analytics_filter when filter is changed ', async () => {
		const { getByText } = render(
			<ReportFilters
				report="test-report"
				query={ {
					page: 'page',
					path: 'path',
				} }
				filters={ [
					{
						filters: [
							{
								label: 'All products',
								value: 'all',
							},
							{
								label: 'Some products',
								value: 'some',
							},
						],
						label: 'Show',
						param: 'filter',
						showFilters: () => true,
						staticParams: [],
					},
				] }
			/>
		);
		userEvent.click( getByText( 'All products' ) );
		userEvent.click( getByText( 'Some products' ) );
		expect( recordEvent ).toHaveBeenCalledWith( 'analytics_filter', {
			filter: 'some',
			report: 'test-report',
		} );
	} );
} );
