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
	MappingChoice,
	PrepareResponse,
	RunChunkResponse,
} from '../data/types';

export type ImporterStep = 'upload' | 'mapping' | 'import' | 'done';

export interface ImporterState {
	step: ImporterStep;
	// Upload step.
	file: File | null;
	// Content of the staged file, read at upload time. The File handle only
	// references the on-disk file, so reading it later fails if the file was
	// moved or edited; the failed-rows export works from this copy instead.
	fileText: string | null;
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
	| { type: 'SET_FILE_TEXT'; text: string | null }
	| { type: 'SET_DELIMITER'; delimiter: string }
	| { type: 'SET_NOTIFY'; value: boolean }
	| { type: 'SET_UPDATE_EXISTING'; value: boolean }
	| { type: 'SET_BUSY'; value: boolean }
	| { type: 'PREPARE_OK'; payload: PrepareResponse }
	| { type: 'SET_MAPPING_FOR_COL'; col: number; value: MappingChoice }
	| { type: 'BACK_TO_UPLOAD' }
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

const MAPPING_CHOICES: ReadonlySet< MappingChoice > = new Set( [
	...CANONICAL_COLUMN_KEYS,
	'skip',
] );

// Values that do not map a column to a field, so any number of columns can hold them.
function isNonField( value: MappingChoice ): boolean {
	return value === '' || value === 'skip';
}

export function createInitialState(): ImporterState {
	return {
		step: 'upload',
		file: null,
		fileText: null,
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
	const taken = new Set< MappingChoice >();
	const next: ColumnMapping = {};
	Object.keys( mapping )
		.map( Number )
		.sort( ( a, b ) => a - b )
		.forEach( ( col ) => {
			const value = mapping[ col ];
			if ( ! isNonField( value ) && taken.has( value ) ) {
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
 * Assign a field to one CSV column, keeping field mappings one-to-one: any
 * other column currently mapped to the same field is cleared, and values
 * outside the known set are treated as unassigned.
 */
function assignMappingForCol(
	mapping: ColumnMapping,
	col: number,
	value: MappingChoice
): ColumnMapping {
	const safeValue = MAPPING_CHOICES.has( value ) ? value : '';
	const next: ColumnMapping = {};
	Object.entries( mapping ).forEach( ( [ key, mapped ] ) => {
		const index = Number( key );
		next[ index ] =
			! isNonField( safeValue ) && mapped === safeValue && index !== col
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
			return { ...state, file: action.file, fileText: null, error: null };
		case 'SET_FILE_TEXT':
			return { ...state, fileText: action.text };
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
			// Keep the merchant's manual mapping when they went back to the
			// upload step and continued with a file of the same shape.
			const sameHeaders =
				state.headers.length > 0 &&
				state.headers.length === action.payload.headers.length &&
				state.headers.every(
					( header, index ) =>
						header === action.payload.headers[ index ]
				);
			return {
				...state,
				step: 'mapping',
				token: action.payload.token,
				// Keep the delimiter the server actually parsed with, so the
				// failed-rows export splits the file the same way.
				delimiter: action.payload.delimiter || state.delimiter,
				headers: action.payload.headers,
				sample: action.payload.sample,
				total: action.payload.total,
				mapping: sameHeaders ? state.mapping : detected,
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
		case 'BACK_TO_UPLOAD':
			// The staged file, delimiter and mapping are all kept so the
			// merchant can swap the file or a setting and continue.
			return {
				...state,
				step: 'upload',
				error: null,
				sessionEnded: false,
			};
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
