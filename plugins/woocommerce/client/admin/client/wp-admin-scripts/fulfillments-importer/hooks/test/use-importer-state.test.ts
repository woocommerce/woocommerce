/**
 * Internal dependencies
 */
import {
	createInitialState,
	hasAllRequiredColumns,
	importerReducer,
} from '../use-importer-state';
import type {
	ImporterSummary,
	PrepareResponse,
	RunChunkResponse,
} from '../../data/types';

const prepareResponse: PrepareResponse = {
	token: 'abc',
	headers: [ 'Order ID', 'Tracking', 'Carrier' ],
	sample: [ '12345', '1Z999', 'UPS' ],
	total: 25,
	detected_mapping: {
		'0': 'order_number',
		'1': 'tracking_number',
		'2': 'shipment_provider',
	},
	delimiter: ',',
};

const chunkResponse: RunChunkResponse = {
	processed: 10,
	total: 25,
	done: false,
	counts: { created: 8, updated: 1, skipped: 1, failed: 0, notified: 0 },
	rows: [],
	errors: [],
};

const summary: ImporterSummary = {
	created: 22,
	updated: 1,
	skipped: 1,
	failed: 1,
	notified: 0,
	rows: [],
};

describe( 'importerReducer', () => {
	it( 'transitions upload → mapping → import → done on the happy path', () => {
		let state = createInitialState();
		expect( state.step ).toBe( 'upload' );

		state = importerReducer( state, {
			type: 'SET_FILE',
			file: new File( [ 'a,b' ], 'a.csv' ),
		} );
		expect( state.file?.name ).toBe( 'a.csv' );

		state = importerReducer( state, {
			type: 'PREPARE_OK',
			payload: prepareResponse,
		} );
		expect( state.step ).toBe( 'mapping' );
		expect( state.token ).toBe( 'abc' );
		expect( state.mapping[ 0 ] ).toBe( 'order_number' );

		state = importerReducer( state, { type: 'GO_IMPORT' } );
		expect( state.step ).toBe( 'import' );

		state = importerReducer( state, {
			type: 'CHUNK_OK',
			payload: chunkResponse,
		} );
		expect( state.processed ).toBe( 10 );
		expect( state.counts.created ).toBe( 8 );

		state = importerReducer( state, { type: 'FINISH', summary } );
		expect( state.step ).toBe( 'done' );
		expect( state.summary?.created ).toBe( 22 );
	} );

	it( 'refuses to leave mapping when required columns are not mapped', () => {
		let state = createInitialState();
		state = importerReducer( state, {
			type: 'PREPARE_OK',
			payload: prepareResponse,
		} );
		state = importerReducer( state, {
			type: 'SET_MAPPING_FOR_COL',
			col: 2,
			value: '',
		} );

		const before = state.step;
		state = importerReducer( state, { type: 'GO_IMPORT' } );
		expect( state.step ).toBe( before );
		expect( state.step ).toBe( 'mapping' );
	} );

	it( 'ERROR clears busy and stores the message; RESET wipes everything', () => {
		let state = createInitialState();
		state = importerReducer( state, { type: 'SET_BUSY', value: true } );
		state = importerReducer( state, {
			type: 'ERROR',
			message: 'Upload failed',
		} );

		expect( state.isBusy ).toBe( false );
		expect( state.error ).toBe( 'Upload failed' );

		state = importerReducer( state, { type: 'RESET' } );
		expect( state ).toEqual( createInitialState() );
	} );

	it( 'SET_MAPPING_FOR_COL clears any other column mapped to the same field', () => {
		let state = createInitialState();
		state = importerReducer( state, {
			type: 'PREPARE_OK',
			payload: prepareResponse,
		} );

		state = importerReducer( state, {
			type: 'SET_MAPPING_FOR_COL',
			col: 2,
			value: 'tracking_number',
		} );

		expect( state.mapping[ 2 ] ).toBe( 'tracking_number' );
		expect( state.mapping[ 1 ] ).toBe( '' );
		expect( state.mapping[ 0 ] ).toBe( 'order_number' );
	} );

	it( 'SET_MAPPING_FOR_COL treats unknown values as "do not import"', () => {
		let state = createInitialState();
		state = importerReducer( state, {
			type: 'PREPARE_OK',
			payload: prepareResponse,
		} );

		state = importerReducer( state, {
			type: 'SET_MAPPING_FOR_COL',
			col: 1,
			value: 'not_a_column' as never,
		} );

		expect( state.mapping[ 1 ] ).toBe( '' );
	} );

	it( 'SET_MAPPING_FOR_COL allows several columns set to "Do not import"', () => {
		let state = createInitialState();
		state = importerReducer( state, {
			type: 'PREPARE_OK',
			payload: prepareResponse,
		} );

		state = importerReducer( state, {
			type: 'SET_MAPPING_FOR_COL',
			col: 1,
			value: 'skip',
		} );
		state = importerReducer( state, {
			type: 'SET_MAPPING_FOR_COL',
			col: 2,
			value: 'skip',
		} );

		expect( state.mapping[ 1 ] ).toBe( 'skip' );
		expect( state.mapping[ 2 ] ).toBe( 'skip' );
	} );

	it( 'SET_FILE clears the cached file text', () => {
		let state = createInitialState();
		state = importerReducer( state, {
			type: 'SET_FILE_TEXT',
			text: 'a,b\n1,2\n',
		} );
		expect( state.fileText ).toBe( 'a,b\n1,2\n' );

		// A newly chosen file must never export rows from the old one.
		state = importerReducer( state, {
			type: 'SET_FILE',
			file: new File( [ 'x,y' ], 'other.csv' ),
		} );
		expect( state.fileText ).toBeNull();
	} );

	it( 'PREPARE_OK stores the delimiter the server parsed with', () => {
		let state = createInitialState();
		state = importerReducer( state, {
			type: 'PREPARE_OK',
			payload: { ...prepareResponse, delimiter: ';' },
		} );
		expect( state.delimiter ).toBe( ';' );

		// An empty delimiter in the response keeps the current one.
		state = importerReducer( state, { type: 'BACK_TO_UPLOAD' } );
		state = importerReducer( state, {
			type: 'PREPARE_OK',
			payload: { ...prepareResponse, delimiter: '' },
		} );
		expect( state.delimiter ).toBe( ';' );
	} );

	it( 'BACK_TO_UPLOAD returns to the upload step and keeps the mapping', () => {
		let state = createInitialState();
		state = importerReducer( state, {
			type: 'PREPARE_OK',
			payload: prepareResponse,
		} );
		state = importerReducer( state, { type: 'BACK_TO_UPLOAD' } );

		expect( state.step ).toBe( 'upload' );
		expect( state.mapping[ 0 ] ).toBe( 'order_number' );
	} );

	it( 'PREPARE_OK keeps manual mapping edits when the headers are unchanged', () => {
		let state = createInitialState();
		state = importerReducer( state, {
			type: 'PREPARE_OK',
			payload: prepareResponse,
		} );
		state = importerReducer( state, {
			type: 'SET_MAPPING_FOR_COL',
			col: 2,
			value: 'skip',
		} );
		state = importerReducer( state, { type: 'BACK_TO_UPLOAD' } );
		state = importerReducer( state, {
			type: 'PREPARE_OK',
			payload: prepareResponse,
		} );

		expect( state.mapping[ 2 ] ).toBe( 'skip' );

		// A file with different headers resets to the fresh detection.
		state = importerReducer( state, { type: 'BACK_TO_UPLOAD' } );
		state = importerReducer( state, {
			type: 'PREPARE_OK',
			payload: {
				...prepareResponse,
				headers: [ 'Other', 'Headers', 'Here' ],
			},
		} );
		expect( state.mapping[ 2 ] ).toBe( 'shipment_provider' );
	} );

	it( 'PREPARE_OK drops duplicate detected fields, first column wins', () => {
		let state = createInitialState();
		state = importerReducer( state, {
			type: 'PREPARE_OK',
			payload: {
				...prepareResponse,
				detected_mapping: {
					'0': 'order_number',
					'1': 'tracking_number',
					'2': 'tracking_number',
				},
			},
		} );

		expect( state.mapping ).toEqual( {
			0: 'order_number',
			1: 'tracking_number',
			2: '',
		} );
	} );

	it( 'hasAllRequiredColumns is true only when every required column is mapped', () => {
		expect( hasAllRequiredColumns( {} ) ).toBe( false );
		expect(
			hasAllRequiredColumns( {
				0: 'order_number',
				1: 'tracking_number',
			} )
		).toBe( false );
		expect(
			hasAllRequiredColumns( {
				0: 'order_number',
				1: 'tracking_number',
				2: 'shipment_provider',
			} )
		).toBe( true );
	} );
} );
