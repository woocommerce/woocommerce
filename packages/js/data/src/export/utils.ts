/**
 * External dependencies
 */
import md5 from 'md5';

/**
 * Internal dependencies
 */
import { getResourceName } from '../utils';
import { ExportArgs } from './types';

export const hashExportArgs = ( args: ExportArgs ) => {
	return md5( getResourceName( 'export', args ) );
};
