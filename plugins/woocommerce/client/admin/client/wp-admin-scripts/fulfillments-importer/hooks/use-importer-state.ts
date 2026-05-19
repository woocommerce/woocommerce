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
	ImporterDelimiter,
	ImporterSummary,
	PrepareResponse,
	RunChunkResponse,
} from '../data/types';

export type ImporterStep = 'upload' | 'mapping' | 'import' | 'done';

export interface ImporterState {
	step: ImporterStep;
	// Upload step.
	file: File | null;
	delimiter: ImporterDelimiter;
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
	};
	// Done step.
	summary: ImporterSummary | null;
	// Ambient.
	error: string | null;
	isBusy: boolean;
}

export type ImporterAction =
	| { type: 'SET_FILE'; file: File | null }
	| { type: 'SET_DELIMITER'; delimiter: ImporterDelimiter }
	| { type: 'SET_NOTIFY'; value: boolean }
	| { type: 'SET_UPDATE_EXISTING'; value: boolean }
	| { type: 'SET_BUSY'; value: boolean }
	| { type: 'PREPARE_OK'; payload: PrepareResponse }
	| { type: 'SET_MAPPING_FOR_COL'; col: number; value: CanonicalColumnKey }
	| { type: 'RESET_MAPPING_TO_DETECTED'; mapping: ColumnMapping }
	| { type: 'GO_IMPORT' }
	| { type: 'CHUNK_OK'; payload: RunChunkResponse }
	| { type: 'FINISH'; summary: ImporterSummary }
	| { type: 'ERROR'; message: string }
	| { type: 'CLEAR_ERROR' }
	| { type: 'RESET' };

export const REQUIRED_COLUMNS: CanonicalColumnKey[] = [
	'order_number',
	'tracking_number',
	'shipment_provider',
];

export function createInitialState(): ImporterState {
	return {
		step: 'upload',
		file: null,
		delimiter: 'auto',
		notifyCustomer: false,
		updateExisting: true,
		token: null,
		headers: [],
		sample: [],
		total: 0,
		mapping: {},
		processed: 0,
		counts: { created: 0, updated: 0, skipped: 0, failed: 0 },
		summary: null,
		error: null,
		isBusy: false,
	};
}

function normalizeMapping(
	wireMapping: PrepareResponse[ 'detected_mapping' ]
): ColumnMapping {
	const out: ColumnMapping = {};
	Object.entries( wireMapping || {} ).forEach( ( [ col, key ] ) => {
		out[ Number( col ) ] = ( key || '' ) as CanonicalColumnKey;
	} );
	return out;
}

export function hasAllRequiredColumns( mapping: ColumnMapping ): boolean {
	const present = new Set( Object.values( mapping ).filter( Boolean ) );
	return REQUIRED_COLUMNS.every( ( required ) => present.has( required ) );
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
			const detected = normalizeMapping( action.payload.detected_mapping );
			return {
				...state,
				step: 'mapping',
				token: action.payload.token,
				headers: action.payload.headers,
				sample: action.payload.sample,
				total: action.payload.total,
				mapping: detected,
				processed: 0,
				counts: { created: 0, updated: 0, skipped: 0, failed: 0 },
				error: null,
				isBusy: false,
			};
		}
		case 'SET_MAPPING_FOR_COL':
			return {
				...state,
				mapping: { ...state.mapping, [ action.col ]: action.value },
			};
		case 'RESET_MAPPING_TO_DETECTED':
			return { ...state, mapping: action.mapping };
		case 'GO_IMPORT':
			if ( ! hasAllRequiredColumns( state.mapping ) ) {
				return state;
			}
			return {
				...state,
				step: 'import',
				processed: 0,
				counts: { created: 0, updated: 0, skipped: 0, failed: 0 },
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
				},
				error: null,
			};
		case 'FINISH':
			return {
				...state,
				step: 'done',
				summary: action.summary,
				isBusy: false,
				error: null,
			};
		case 'ERROR':
			return { ...state, error: action.message, isBusy: false };
		case 'CLEAR_ERROR':
			return { ...state, error: null };
		case 'RESET':
			return createInitialState();
		default:
			return state;
	}
}

export function useImporterState() {
	return useReducer( importerReducer, undefined, createInitialState );
}
