/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import type {
	ColumnMapping,
	ImporterDelimiter,
	ImporterSummary,
	PrepareResponse,
	RunChunkResponse,
} from './types';

const DEFAULT_BASE = '/wc/v3/fulfillments/import';

/**
 * Use the REST base localized by PHP when present so the route stays in sync
 * with FulfillmentsCsvImporterController. Falls back to the default if the
 * settings object is not on the page.
 */
function getBase(): string {
	const route = window.wcFulfillmentsImporterSettings?.importRoute;
	return typeof route === 'string' && route.length > 0 ? route : DEFAULT_BASE;
}

export interface PrepareArgs {
	file: File;
	delimiter: ImporterDelimiter;
	notifyCustomer: boolean;
	updateExisting: boolean;
}

export interface RunArgs {
	token: string;
	offset: number;
	limit: number;
	mapping: ColumnMapping;
	notifyCustomer: boolean;
	updateExisting: boolean;
	signal?: AbortSignal;
}

/**
 * Multipart wrapper for the prepare step: stages the upload, parses headers, opens an ImportSession.
 */
export async function prepare( args: PrepareArgs ): Promise< PrepareResponse > {
	const body = new FormData();
	body.append( 'file', args.file );
	body.append( 'delimiter', args.delimiter );
	body.append( 'notify_customer', args.notifyCustomer ? '1' : '0' );
	body.append( 'update_existing', args.updateExisting ? '1' : '0' );

	return apiFetch< PrepareResponse >( {
		path: `${ getBase() }/prepare`,
		method: 'POST',
		body,
	} );
}

/**
 * Process a single chunk against an open session. Throws on transport or REST error.
 */
export async function runChunk( args: RunArgs ): Promise< RunChunkResponse > {
	const mappingForWire: Record< string, string > = {};
	Object.entries( args.mapping ).forEach( ( [ col, key ] ) => {
		if ( key ) {
			mappingForWire[ col ] = key;
		}
	} );

	return apiFetch< RunChunkResponse >( {
		path: `${ getBase() }/run`,
		method: 'POST',
		data: {
			token: args.token,
			offset: args.offset,
			limit: args.limit,
			mapping: mappingForWire,
			options: {
				notify_customer: args.notifyCustomer,
				update_existing: args.updateExisting,
			},
		},
		signal: args.signal,
	} );
}

/**
 * Type guard for the optional summary attached to the final chunk's response.
 */
export function isFinalChunk(
	response: RunChunkResponse
): response is RunChunkResponse & { summary: ImporterSummary } {
	return response.done === true && !! response.summary;
}
