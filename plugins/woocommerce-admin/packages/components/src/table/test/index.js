/** @format */
/**
 * External dependencies
 */
import fetch from 'node-fetch';
import { mount, shallow } from 'enzyme';

/**
 * WooCommerce dependencies
 */
import { downloadCSVFile, generateCSVFileName } from '@woocommerce/csv-export';

/**
 * Internal dependencies
 */
import TableCard from '../index';
import mockHeaders from '../__mocks__/table-mock-headers';
import mockData from '../__mocks__/table-mock-data';
import mockCSV from '../__mocks__/table-mock-csv';

jest.mock( '@woocommerce/csv-export', () => ( {
	...require.requireActual( '@woocommerce/csv-export' ),
	generateCSVFileName: jest.fn().mockReturnValue( 'filename.csv' ),
	downloadCSVFile: jest.fn(),
} ) );

window.fetch = fetch;

describe( 'TableCard', () => {
	test( 'should render placeholder table while loading', () => {
		const tableCard = shallow(
			<TableCard
				title="Revenue"
				headers={ mockHeaders }
				isLoading={ true }
				rows={ [] }
				rowsPerPage={ 5 }
				totalRows={ 5 }
			/>
		);

		expect( tableCard.find( 'TablePlaceholder' ).length ).toBe( 1 );
	} );

	test( 'should not render placeholder table when not loading', () => {
		const tableCard = mount(
			<TableCard
				title="Revenue"
				headers={ mockHeaders }
				isLoading={ false }
				rows={ mockData }
				rowsPerPage={ 5 }
				totalRows={ 5 }
			/>
		);

		expect( tableCard.find( 'Table' ).length ).toBe( 1 );
		expect( tableCard.find( 'TablePlaceholder' ).length ).toBe( 0 );
	} );

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
			showCols: [ 'date', 'orders_count', 'gross_revenue', 'refunds', 'taxes', 'shipping', 'net_revenue' ],
		} );

		const downloadButton = tableCard.findWhere(
			node => node.props().className === 'woocommerce-table__download-button'
		);
		downloadButton.props().onClick();

		expect( downloadCSVFile ).toHaveBeenCalledWith( 'filename.csv', mockCSV );
		expect( generateCSVFileName ).toHaveBeenCalledWith( 'Revenue', {} );
	} );
} );
