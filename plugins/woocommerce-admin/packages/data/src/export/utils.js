/**
 * External dependencies
 */
import md5 from 'md5';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';

export const hashExportArgs = ( args ) => {
	return md5( getResourceName( 'export', args ) );
};
