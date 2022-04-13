/**
 * Internal dependencies
 */
import { fetchWithHeaders } from '../controls';
import TYPES from './action-types';
import { NAMESPACE } from '../constants';

export function setExportId( exportType, exportArgs, exportId ) {
	return {
		type: TYPES.SET_EXPORT_ID,
		exportType,
		exportArgs,
		exportId,
	};
}

export function setIsRequesting( selector, selectorArgs, isRequesting ) {
	return {
		type: TYPES.SET_IS_REQUESTING,
		selector,
		selectorArgs,
		isRequesting,
	};
}

export function setError( selector, selectorArgs, error ) {
	return {
		type: TYPES.SET_ERROR,
		selector,
		selectorArgs,
		error,
	};
}

export function* startExport( type, args ) {
	yield setIsRequesting( 'startExport', { type, args }, true );

	try {
		const response = yield fetchWithHeaders( {
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
		yield setError( 'startExport', { type, args }, error.message );
		yield setIsRequesting( 'startExport', { type, args }, false );
		throw error;
	}
}
