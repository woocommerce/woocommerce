/**
 * External dependencies
 */
import crypto from 'crypto';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';

export const hashExportArgs = ( args ) => {
	return crypto
		.createHash( 'md5' )
		.update( getResourceName( 'export', args ) )
		.digest( 'hex' );
};
