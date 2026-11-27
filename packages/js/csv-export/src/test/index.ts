/* eslint-disable jest/no-mocks-import */
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
			// Embedded quotes must be doubled so they cannot end the escaped cell early.
			{
				input: '=1",=HYPERLINK("http://example.com"),"',
				expected: `"'=1"",=HYPERLINK(""http://example.com""),"""`,
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
	beforeEach( () => {
		jest.useFakeTimers().setSystemTime( new Date( '2024-12-23' ) );
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

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
	it( 'downloads UTF-8 CSV with the requested filename and releases the object URL', async () => {
		const originalURL = window.URL;
		const createObjectURL = jest.fn< string, [ Blob ] >(
			() => 'blob:test'
		);
		const revokeObjectURL = jest.fn();
		const click = jest
			.spyOn( window.HTMLAnchorElement.prototype, 'click' )
			.mockImplementation( function (
				this: InstanceType< typeof window.HTMLAnchorElement >
			) {
				expect( this.download ).toBe( 'test.csv' );
				expect( this.href ).toBe( 'blob:test' );
			} );

		jest.useFakeTimers();
		window.URL = Object.assign( class extends originalURL {}, {
			createObjectURL,
			revokeObjectURL,
		} );

		try {
			const data = 'Product,Price\n商品 🛒 Café,19.99';
			downloadCSVFile( 'test.csv', data );

			expect( click ).toHaveBeenCalledTimes( 1 );
			const [ blob ] = createObjectURL.mock.calls[ 0 ];
			expect( blob.type ).toBe( 'text/csv;charset=utf-8' );

			const bytes = await new Promise< Uint8Array >(
				( resolve, reject ) => {
					const reader = new window.FileReader();
					reader.onload = () =>
						resolve(
							new Uint8Array( reader.result as ArrayBuffer )
						);
					reader.onerror = () => reject( reader.error );
					reader.readAsArrayBuffer( blob );
				}
			);
			expect( Array.from( bytes.slice( 0, 3 ) ) ).toEqual( [
				239, 187, 191,
			] );
			expect( new TextDecoder().decode( bytes ) ).toBe( data );

			expect( revokeObjectURL ).not.toHaveBeenCalled();
			jest.runOnlyPendingTimers();
			expect( revokeObjectURL ).toHaveBeenCalledWith( 'blob:test' );
		} finally {
			window.URL = originalURL;
			click.mockRestore();
			jest.useRealTimers();
		}
	} );

	it( 'falls back to a default filename when none is given', () => {
		const originalURL = window.URL;
		let download: string | undefined;
		const click = jest
			.spyOn( window.HTMLAnchorElement.prototype, 'click' )
			.mockImplementation( function (
				this: InstanceType< typeof window.HTMLAnchorElement >
			) {
				download = this.download;
			} );

		jest.useFakeTimers();
		window.URL = Object.assign( class extends originalURL {}, {
			createObjectURL: () => 'blob:test',
			revokeObjectURL: jest.fn(),
		} );

		try {
			downloadCSVFile( '', 'Product,Price' );

			expect( download ).toBe( 'download' );
		} finally {
			window.URL = originalURL;
			click.mockRestore();
			jest.useRealTimers();
		}
	} );
} );
