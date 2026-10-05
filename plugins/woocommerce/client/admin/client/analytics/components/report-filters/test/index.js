import { describe, expect, test } from 'vitest';

/**
 * External dependencies
 */
import { render, act } from '@testing-library/react';
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
				path="path"
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
		await act( async () => {
			userEvent.click( getByText( 'All products' ) );
		} );
		await act( async () => {
			userEvent.click( getByText( 'Some products' ) );
		} );
		expect( recordEvent ).toHaveBeenCalledWith( 'analytics_filter', {
			filter: 'some',
			report: 'test-report',
		} );
	} );
} );
