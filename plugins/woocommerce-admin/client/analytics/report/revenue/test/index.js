/**
 * External dependencies
 *
 * @format
 */
import fetch from 'node-fetch';
import moment from 'moment';
import { shallow } from 'enzyme';
import { TableCard } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { RevenueReport } from '../';
import mockData from '../__mocks__/revenue-mock-data';
import mockCSV from '../__mocks__/revenue-mock-csv';
import { downloadCSVFile } from 'lib/csv';

jest.mock( 'lib/csv', () => ( {
	...require.requireActual( 'lib/csv' ),
	downloadCSVFile: jest.fn(),
} ) );

window.fetch = fetch;

describe( 'RevenueReport', () => {
	test( 'should save CSV when clicking on download', () => {
		global.Blob = ( content, options ) => ( { content, options } );

		const primaryData = {
			data: mockData,
			totalResults: 7,
			totalPages: 1,
		};

		const summaryNumbers = {
			totals: {
				primary: {},
				secondary: {},
			},
		};

		const revenueReport = shallow(
			<RevenueReport
				params={ { report: 'revenue' } }
				path="/analytics/revenue"
				query={ {} }
				summaryNumbers={ summaryNumbers }
				primaryData={ primaryData }
				tableData={ primaryData }
				secondaryData={ primaryData }
			/>
		);

		const tableCard = revenueReport.find( TableCard );
		tableCard.props().onClickDownload();

		expect( downloadCSVFile ).toHaveBeenCalledWith(
			'revenue-' + moment().format( 'YYYY-MM-DD' ) + '.csv',
			mockCSV
		);
	} );
} );
