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

describe( 'ReportFilters', () => {
	test( 'should record analytics_filter Tracks event when filter is changed', async () => {
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
