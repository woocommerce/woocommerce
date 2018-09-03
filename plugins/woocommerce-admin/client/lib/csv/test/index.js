/** @format */
/**
 * External dependencies
 */
import moment from 'moment';
import { saveAs } from 'browser-filesaver';

/**
 * Internal dependencies
 */
import { downloadCSVFile, generateCSVDataFromTable, generateCSVFileName } from '../index';
import mockCSVData from '../__mocks__/mock-csv-data';
import mockHeaders from '../__mocks__/mock-headers';
import mockRows from '../__mocks__/mock-rows';

jest.mock( 'browser-filesaver', () => ( {
	saveAs: jest.fn(),
} ) );

describe( 'generateCSVDataFromTable', () => {
	it( 'should not crash when parameters are not arrays', () => {
		expect( generateCSVDataFromTable( null, null ) ).toBe( '' );
	} );

	it( 'should generate a CSV string from table contents', () => {
		expect( generateCSVDataFromTable( mockHeaders, mockRows ) ).toBe( mockCSVData );
	} );
} );

describe( 'generateCSVFileName', () => {
	it( 'should generate a file name with the date when no params are provided', () => {
		const fileName = generateCSVFileName();
		expect( fileName ).toBe( moment().format( 'YYYY-MM-DD' ) + '.csv' );
	} );

	it( 'should generate a file name with the `name` and the date', () => {
		const fileName = generateCSVFileName( 'revenue' );
		expect( fileName ).toBe( 'revenue-' + moment().format( 'YYYY-MM-DD' ) + '.csv' );
	} );

	it( 'should generate a file name with the `name` and `params`', () => {
		const fileName = generateCSVFileName( 'revenue', { orderby: 'revenue', order: 'desc' } );
		expect( fileName ).toBe(
			'revenue-' + moment().format( 'YYYY-MM-DD' ) + '-orderby-revenue-order-desc.csv'
		);
	} );
} );

describe( 'downloadCSVFile', () => {
	it( "should download a CSV file name to users' browser", () => {
		global.Blob = ( content, options ) => ( { content, options } );
		const fileName = 'test.csv';
		downloadCSVFile( fileName, mockCSVData );

		const blob = new Blob( [ mockCSVData ], { type: 'text/csv;charset=utf-8' } );

		expect( saveAs ).toHaveBeenCalledWith( blob, fileName );
	} );
} );
