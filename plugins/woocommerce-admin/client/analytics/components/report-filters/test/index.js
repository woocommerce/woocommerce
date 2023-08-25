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

jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );
	return {
		...Object.keys( originalModule ).reduce( ( mocked, key ) => {
			try {
				mocked[ key ] = originalModule[ key ];
			} catch ( e ) {
				mocked[ key ] = jest.fn();
			}
			return mocked;
		}, {} ),
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

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
		userEvent.click( getByText( 'All products' ) );
		userEvent.click( getByText( 'Some products' ) );
		expect( recordEvent ).toHaveBeenCalledWith( 'analytics_filter', {
			filter: 'some',
			report: 'test-report',
		} );
	} );
} );
