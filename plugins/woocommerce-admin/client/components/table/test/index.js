/**
 * External dependencies
 *
 * @format
 */
import fetch from 'node-fetch';
import moment from 'moment';
import { mount } from 'enzyme';

/**
 * Internal dependencies
 */
import TableCard from '../index';
import mockHeaders from '../__mocks__/table-mock-headers';
import mockData from '../__mocks__/table-mock-data';
import mockCSV from '../__mocks__/table-mock-csv';
import { downloadCSVFile } from 'lib/csv';

jest.mock( 'lib/csv', () => ( {
	...require.requireActual( 'lib/csv' ),
	downloadCSVFile: jest.fn(),
} ) );

window.fetch = fetch;

describe( 'TableCard', () => {
	test( 'should save CSV when clicking on download', () => {
		global.Blob = ( content, options ) => ( { content, options } );

		const tableCard = mount(
			<TableCard
				title="Revenue"
				rows={ mockData }
				totalRows={ mockData.length }
				rowsPerPage={ mockData.length }
				headers={ mockHeaders }
				onQueryChange={ () => null }
				query={ {} }
				summary={ null }
				downloadable
			/>
		);
		tableCard.setState( {
			showCols: [ true, true, true, true, false, true, true, true ],
		} );

		const downloadButton = tableCard.findWhere(
			node => node.props().className === 'woocommerce-table__download-button'
		);
		downloadButton.props().onClick();

		expect( downloadCSVFile ).toHaveBeenCalledWith(
			'revenue-' + moment().format( 'YYYY-MM-DD' ) + '.csv',
			mockCSV
		);
	} );
} );
