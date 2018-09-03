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
import RevenueReport from '../';
import mockData from '../__mocks__/mock-data';
import mockCSV from '../__mocks__/mock-csv';
import { downloadCSVFile } from 'lib/csv';

jest.mock( 'lib/csv', () => ( {
	...require.requireActual( 'lib/csv' ),
	downloadCSVFile: jest.fn(),
} ) );

window.fetch = fetch;

describe( 'RevenueReport', () => {
	test( 'should save CSV when clicking on download', () => {
		global.Blob = ( content, options ) => ( { content, options } );

		const revenueReport = shallow(
			<RevenueReport params={ { report: 'revenue' } } path="/analytics/revenue" query={ {} } />
		);
		revenueReport.setState( { stats: mockData } );
		const tableCard = revenueReport.find( TableCard );
		tableCard.props().onClickDownload();

		expect( downloadCSVFile ).toHaveBeenCalledWith(
			'revenue-' + moment().format( 'YYYY-MM-DD' ) + '.csv',
			mockCSV
		);
	} );
} );
