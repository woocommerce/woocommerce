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
import RevenueReportTable from '../table';
import mockData from '../__mocks__/revenue-mock-data';
import mockCSV from '../__mocks__/revenue-mock-csv';
import { downloadCSVFile } from 'lib/csv';

jest.mock( 'lib/csv', () => ( {
	...require.requireActual( 'lib/csv' ),
	downloadCSVFile: jest.fn(),
} ) );

window.fetch = fetch;

describe( 'RevenueReportTable', () => {
	test( 'should save CSV when clicking on download', () => {
		global.Blob = ( content, options ) => ( { content, options } );

		const tableData = {
			data: mockData,
		};

		const revenueReportTable = shallow(
			<RevenueReportTable
				isError={ false }
				isRequesting={ false }
				tableData={ tableData }
				query={ {} }
				totalRows={ mockData.intervals.length }
			/>
		);

		const tableCard = revenueReportTable.find( TableCard );
		tableCard.props().onClickDownload();

		expect( downloadCSVFile ).toHaveBeenCalledWith(
			'revenue-' + moment().format( 'YYYY-MM-DD' ) + '.csv',
			mockCSV
		);
	} );
} );
