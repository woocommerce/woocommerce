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
import mockCSVData from './__mocks__/mock-csv-data';
import mockHeaders from './__mocks__/mock-headers';
import mockRows from './__mocks__/mock-rows';

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
		const testValues = [
			// The values below should be escaped to prevent CSV formula injection.
			{ input: '=danger', expected: `"'=danger"` },
			{ input: '+danger', expected: `"'+danger"` },
			{ input: '-danger', expected: `"'-danger"` },
			{ input: '@danger', expected: `"'@danger"` },
			{
				input: String.fromCharCode( 0x09 ) + 'danger',
				expected: `"'${ String.fromCharCode( 0x09 ) }danger"`,
			},
			{
				input: String.fromCharCode( 0x0d ) + 'danger',
				expected: `"'${ String.fromCharCode( 0x0d ) }danger"`,
			},

			// The values below should not be escaped since they are pure numeric values.
			{ input: 12, expected: '12' },
			{ input: 12.34, expected: '12.34' },
			{ input: -12, expected: '-12' },
			{ input: -12.34, expected: '-12.34' },
			{
				input: Number.MIN_SAFE_INTEGER,
				expected: '-9007199254740991',
			},
		];

		testValues.forEach( ( { input, expected } ) => {
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
							value: input,
						},
					],
				]
			);
			expect( result ).toBe( `value\n${ expected }` );
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
		const fileName = 'test.csv';
		downloadCSVFile( fileName, mockCSVData );

		// Get the Blob that was passed to saveAs
		const [ blob ] = ( saveAs as jest.Mock ).mock.calls[ 0 ];

		// Verify it's a Blob with the correct content
		expect( blob ).toBeInstanceOf( Blob );
		expect( blob.type ).toBe( 'text/csv;charset=utf-8' );

		// If you need to verify the content:
		const reader = new FileReader();
		reader.readAsText( blob );
		reader.onload = () => {
			expect( reader.result ).toBe( mockCSVData );
		};

		expect( saveAs ).toHaveBeenCalledWith( expect.any( Blob ), fileName );
	} );
} );
