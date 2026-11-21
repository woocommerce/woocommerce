/**
 * Internal dependencies
 */
import { readJsonDataset } from '../read-json-dataset';

describe( 'readJsonDataset', () => {
	it( 'returns null for a missing attribute', () => {
		expect( readJsonDataset( undefined ) ).toBeNull();
	} );

	it( 'returns null when the attribute is empty, which is what a failed encode leaves', () => {
		expect( readJsonDataset( '' ) ).toBeNull();
	} );

	it( 'returns null for JSON that does not parse, instead of throwing', () => {
		expect( readJsonDataset( '{bad' ) ).toBeNull();
	} );

	it( 'returns the parsed value', () => {
		expect( readJsonDataset( '{"connected":false}' ) ).toEqual( {
			connected: false,
		} );
	} );
} );
