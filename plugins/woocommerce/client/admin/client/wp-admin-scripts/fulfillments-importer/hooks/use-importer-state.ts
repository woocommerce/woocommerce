/**
 * External dependencies
 */
import { useReducer } from 'react';

/**
 * Internal dependencies
 */
import type {
	CanonicalColumnKey,
	ColumnMapping,
	ImporterRowResult,
	ImporterSummary,
	PrepareResponse,
	RunChunkResponse,
} from '../data/types';

export type ImporterStep = 'upload' | 'mapping' | 'import' | 'done';

export interface ImporterState {
	step: ImporterStep;
	// Upload step.
	file: File | null;
	delimiter: string;
	notifyCustomer: boolean;
	updateExisting: boolean;
	// Mapping step.
	token: string | null;
	headers: string[];
	sample: string[];
	total: number;
	mapping: ColumnMapping;
	// Import step.
	processed: number;
	counts: {
		created: number;
		updated: number;
		skipped: number;
		failed: number;
		notified: number;
	};
	rows: ImporterRowResult[];
	// Done step.
	summary: ImporterSummary | null;
	// Ambient.
	error: string | null;
	// True when the error ended the server-side session, so retrying cannot succeed.
	sessionEnded: boolean;
	isBusy: boolean;
}

export type ImporterAction =
	| { type: 'SET_FILE'; file: File | null }
	| { type: 'SET_DELIMITER'; delimiter: string }
	| { type: 'SET_NOTIFY'; value: boolean }
	| { type: 'SET_UPDATE_EXISTING'; value: boolean }
	| { type: 'SET_BUSY'; value: boolean }
	| { type: 'PREPARE_OK'; payload: PrepareResponse }
	| { type: 'SET_MAPPING_FOR_COL'; col: number; value: CanonicalColumnKey }
	| { type: 'RESET_MAPPING_TO_DETECTED'; mapping: ColumnMapping }
	| { type: 'GO_IMPORT' }
	| { type: 'CHUNK_OK'; payload: RunChunkResponse }
	| { type: 'FINISH'; summary: ImporterSummary }
	| { type: 'ERROR'; message: string; sessionEnded?: boolean }
	| { type: 'CLEAR_ERROR' }
	| { type: 'RESET' };

export const REQUIRED_COLUMNS: CanonicalColumnKey[] = [
	'order_number',
	'tracking_number',
	'shipment_provider',
];

const CANONICAL_COLUMN_KEYS: ReadonlySet< CanonicalColumnKey > = new Set( [
	'',
	'order_number',
	'tracking_number',
	'shipment_provider',
	'tracking_url',
	'items',
] );

export function createInitialState(): ImporterState {
	return {
		step: 'upload',
		file: null,
		delimiter: ',',
		notifyCustomer: false,
		updateExisting: true,
		token: null,
		headers: [],
		sample: [],
		total: 0,
		mapping: {},
		processed: 0,
		counts: {
			created: 0,
			updated: 0,
			skipped: 0,
			failed: 0,
			notified: 0,
		},
		rows: [],
		summary: null,
		error: null,
		sessionEnded: false,
		isBusy: false,
	};
}

/**
 * Drop repeated canonical fields from a detected mapping, keeping the first
 * column (lowest index) that maps to each field.
 */
function uniqueMapping( mapping: ColumnMapping ): ColumnMapping {
	const taken = new Set< CanonicalColumnKey >();
	const next: ColumnMapping = {};
	Object.keys( mapping )
		.map( Number )
		.sort( ( a, b ) => a - b )
		.forEach( ( col ) => {
			const value = mapping[ col ];
			if ( value !== '' && taken.has( value ) ) {
				next[ col ] = '';
				return;
			}
			taken.add( value );
			next[ col ] = value;
		} );
	return next;
}

function normalizeMapping(
	wireMapping: PrepareResponse[ 'detected_mapping' ]
): ColumnMapping {
	const out: ColumnMapping = {};
	Object.entries( wireMapping || {} ).forEach( ( [ col, key ] ) => {
		const candidate = ( key || '' ) as CanonicalColumnKey;
		out[ Number( col ) ] = CANONICAL_COLUMN_KEYS.has( candidate )
			? candidate
			: '';
	} );
	return uniqueMapping( out );
}

export function hasAllRequiredColumns( mapping: ColumnMapping ): boolean {
	const present = new Set( Object.values( mapping ).filter( Boolean ) );
	return REQUIRED_COLUMNS.every( ( required ) => present.has( required ) );
}

/**
 * Assign a canonical field to one CSV column, keeping the mapping one-to-one:
 * any other column currently mapped to the same field is cleared, and values
 * outside the canonical set are treated as "do not import".
 */
function assignMappingForCol(
	mapping: ColumnMapping,
	col: number,
	value: CanonicalColumnKey
): ColumnMapping {
	const safeValue = CANONICAL_COLUMN_KEYS.has( value ) ? value : '';
	const next: ColumnMapping = {};
	Object.entries( mapping ).forEach( ( [ key, mapped ] ) => {
		const index = Number( key );
		next[ index ] =
			safeValue !== '' && mapped === safeValue && index !== col
				? ''
				: mapped;
	} );
	next[ col ] = safeValue;
	return next;
}

export function importerReducer(
	state: ImporterState,
	action: ImporterAction
): ImporterState {
	switch ( action.type ) {
		case 'SET_FILE':
			return { ...state, file: action.file, error: null };
		case 'SET_DELIMITER':
			return { ...state, delimiter: action.delimiter };
		case 'SET_NOTIFY':
			return { ...state, notifyCustomer: action.value };
		case 'SET_UPDATE_EXISTING':
			return { ...state, updateExisting: action.value };
		case 'SET_BUSY':
			return { ...state, isBusy: action.value };
		case 'PREPARE_OK': {
			const detected = normalizeMapping(
				action.payload.detected_mapping
			);
			return {
				...state,
				step: 'mapping',
				token: action.payload.token,
				headers: action.payload.headers,
				sample: action.payload.sample,
				total: action.payload.total,
				mapping: detected,
				processed: 0,
				counts: {
					created: 0,
					updated: 0,
					skipped: 0,
					failed: 0,
					notified: 0,
				},
				rows: [],
				error: null,
				isBusy: false,
			};
		}
		case 'SET_MAPPING_FOR_COL':
			return {
				...state,
				mapping: assignMappingForCol(
					state.mapping,
					action.col,
					action.value
				),
			};
		case 'RESET_MAPPING_TO_DETECTED':
			return { ...state, mapping: uniqueMapping( action.mapping ) };
		case 'GO_IMPORT':
			if ( ! hasAllRequiredColumns( state.mapping ) ) {
				return state;
			}
			return {
				...state,
				step: 'import',
				processed: 0,
				counts: {
					created: 0,
					updated: 0,
					skipped: 0,
					failed: 0,
					notified: 0,
				},
				rows: [],
				error: null,
			};
		case 'CHUNK_OK':
			return {
				...state,
				processed: action.payload.processed,
				counts: {
					created: action.payload.counts.created,
					updated: action.payload.counts.updated,
					skipped: action.payload.counts.skipped,
					failed: action.payload.counts.failed,
					notified: action.payload.counts.notified,
				},
				rows: state.rows.concat( action.payload.rows ?? [] ),
				error: null,
			};
		case 'FINISH':
			return {
				...state,
				step: 'done',
				summary: {
					...action.summary,
					rows: state.rows,
				},
				isBusy: false,
				error: null,
			};
		case 'ERROR':
			return {
				...state,
				error: action.message,
				sessionEnded: action.sessionEnded === true,
				isBusy: false,
			};
		case 'CLEAR_ERROR':
			return { ...state, error: null, sessionEnded: false };
		case 'RESET':
			return createInitialState();
		default:
			return state;
	}
}

export function useImporterState() {
	return useReducer( importerReducer, undefined, createInitialState );
}
