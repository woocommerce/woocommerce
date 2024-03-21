/* eslint-disable jest/no-mocks-import */
/**
 * External dependencies
 */
import { saveAs } from 'browser-filesaver';

/**
 * Internal dependencies
 */
import {
	downloadCSVFile,
	generateCSVDataFromTable,
	generateCSVFileName,
} from '../index';
import mockCSVData from '../__mocks__/mock-csv-data';
import mockHeaders from '../__mocks__/mock-headers';
import mockRows from '../__mocks__/mock-rows';

jest.mock( 'browser-filesaver', () => ( {
	saveAs: jest.fn(),
} ) );

describe( 'generateCSVDataFromTable', () => {
	it( 'should not crash when parameters are not arrays', () => {
		// @ts-expect-error generateCSVDataFromTable() should only accept arrays.
		expect( generateCSVDataFromTable( null, null ) ).toBe( '' );
	} );

	it( 'should generate a CSV string from table contents', () => {
		expect( generateCSVDataFromTable( mockHeaders, mockRows ) ).toBe(
			mockCSVData
		);
	} );

	it( 'should prefix single quote character when the cell value starts with one of =, +, -, @, tab, and carriage return', () => {
		[
			'=',
			'+',
			'-',
			'@',
			String.fromCharCode( 0x09 ), // tab
			String.fromCharCode( 0x0d ), // carriage return
		].forEach( ( val ) => {
			const expected = 'value\n"\'' + val + 'test"';
			const result = generateCSVDataFromTable(
				[
					{
						label: 'value',
						key: 'value',
					},
				],
				[
					[
						{
							display: 'value',
							value: val + 'test',
						},
					],
				]
			);
			expect( result ).toBe( expected );
		} );
	} );
} );

describe( 'generateCSVFileName', () => {
	jest.useFakeTimers().setSystemTime( new Date( '2024-12-23' ) );

	it( 'should generate a file name with the date when no params are provided', () => {
		const fileName = generateCSVFileName();
		expect( fileName ).toBe( '2024-12-23.csv' );
	} );

	it( 'should generate a file name with the `name` and the date', () => {
		const fileName = generateCSVFileName( 'Revenue table' );
		expect( fileName ).toBe( 'revenue-table_2024-12-23.csv' );
	} );

	it( 'should generate a file name with the `name` and `params`', () => {
		const fileName = generateCSVFileName( 'Revenue table', {
			orderby: 'revenue',
			order: 'desc',
		} );
		expect( fileName ).toBe(
			'revenue-table_2024-12-23_orderby-revenue_order-desc.csv'
		);
	} );
} );

describe( 'downloadCSVFile', () => {
	it( "should download a CSV file name to users' browser", () => {
		const mockFn = jest.fn();
		jest.spyOn( global, 'Blob' ).mockImplementation(
			(
				content?: BlobPart[] | undefined,
				options?: BlobPropertyBag | undefined
			) => {
				return {
					content,
					options,
					size: 0,
					type: '',
					arrayBuffer: mockFn,
					slice: mockFn,
					stream: mockFn,
					text: mockFn,
				};
			}
		);
		const fileName = 'test.csv';
		downloadCSVFile( fileName, mockCSVData );
		// eslint-disable-next-line no-undef
		const blob = new Blob( [ mockCSVData ], {
			type: 'text/csv;charset=utf-8',
		} );

		expect( saveAs ).toHaveBeenCalledWith( blob, fileName );
	} );
} );
