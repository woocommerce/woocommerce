/**
 * Internal dependencies
 */
import { fetchWithHeaders, FetchWithHeadersResponse } from '../controls';
import TYPES from './action-types';
import { NAMESPACE } from '../constants';
import { SelectorArgs, ExportArgs } from './types';

export function setExportId(
	exportType: string,
	exportArgs: ExportArgs,
	exportId: string
) {
	return {
		type: TYPES.SET_EXPORT_ID,
		exportType,
		exportArgs,
		exportId,
	};
}

export function setIsRequesting(
	selector: string,
	selectorArgs: SelectorArgs,
	isRequesting: boolean
) {
	return {
		type: TYPES.SET_IS_REQUESTING,
		selector,
		selectorArgs,
		isRequesting,
	};
}

export function setError(
	selector: string,
	selectorArgs: SelectorArgs,
	error: unknown
) {
	return {
		type: TYPES.SET_ERROR,
		selector,
		selectorArgs,
		error,
	};
}

export function* startExport( type: string, args: ExportArgs ) {
	yield setIsRequesting( 'startExport', { type, args }, true );

	try {
		const response: FetchWithHeadersResponse< {
			export_id: string;
			message: string;
		} > = yield fetchWithHeaders( {
			path: `${ NAMESPACE }/reports/${ type }/export`,
			method: 'POST',
			data: {
				report_args: args,
				email: true,
			},
		} );

		yield setIsRequesting( 'startExport', { type, args }, false );
		const { export_id: exportId, message } = response.data;

		if ( exportId ) {
			yield setExportId( type, args, exportId );
		} else {
			throw new Error( message );
		}

		return response.data;
	} catch ( error ) {
		if ( error instanceof Error ) {
			yield setError( 'startExport', { type, args }, error.message );
		} else {
			// eslint-disable-next-line no-console
			console.error( `Unexpected Error: ${ JSON.stringify( error ) }` );
			// eslint-enable-next-line no-console
		}
		yield setIsRequesting( 'startExport', { type, args }, false );
		throw error;
	}
}

export type Action = ReturnType<
	typeof setExportId | typeof setError | typeof setIsRequesting
>;
