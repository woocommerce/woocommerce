/**
 * @jest-environment node
 */

/**
 * External dependencies
 */
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { getImportStatus } from '../resolvers';
import { NAMESPACE } from '../../constants';

describe( 'getImportStatus resolver', () => {
	it( 'requests the status without query args for a timestamp query', () => {
		const generator = getImportStatus( 1700000000 );

		expect( generator.next().value ).toEqual(
			apiFetch( { path: `${ NAMESPACE }/reports/import/status` } )
		);
	} );

	it( 'drops the timestamp from an object query', () => {
		const generator = getImportStatus( {
			timestamp: 1700000000,
			skip_existing: true,
		} as unknown as number );

		expect( generator.next().value ).toEqual(
			apiFetch( {
				path: `${ NAMESPACE }/reports/import/status?skip_existing=true`,
			} )
		);
	} );
} );
