/**
 * Internal dependencies
 */
import { DEFAULT_QUERY } from '../constants';
import { ProductCollectionQuery } from '../types';
import { getDefaultQueryForSettingsSection, getUpdatedQuery } from '../utils';

const getQuery = (
	query: Partial< ProductCollectionQuery > = {}
): ProductCollectionQuery => ( {
	...DEFAULT_QUERY,
	...query,
} );

describe( 'archivePerPage', () => {
	it( 'is unset by default so the store default applies', () => {
		expect( DEFAULT_QUERY.archivePerPage ).toBeUndefined();
	} );

	it( 'is stored on the query when the archive control sets it', () => {
		expect(
			getUpdatedQuery( getQuery( { inherit: true } ), {
				archivePerPage: 24,
			} ).archivePerPage
		).toBe( 24 );
	} );

	it( 'is cleared when the archive control is reset', () => {
		expect(
			getUpdatedQuery(
				getQuery( { inherit: true, archivePerPage: 24 } ),
				{
					archivePerPage: undefined,
				}
			).archivePerPage
		).toBeUndefined();
	} );

	it( 'is cleared by "Reset all" in the settings panel', () => {
		expect(
			getDefaultQueryForSettingsSection(
				getQuery( { inherit: true, archivePerPage: 24 } )
			).archivePerPage
		).toBeUndefined();
	} );

	it( 'leaves the custom-query products per page untouched', () => {
		const query = getUpdatedQuery( getQuery( { perPage: 12 } ), {
			archivePerPage: 24,
		} );

		expect( query.perPage ).toBe( 12 );
		expect( query.archivePerPage ).toBe( 24 );
	} );
} );
