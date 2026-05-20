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
