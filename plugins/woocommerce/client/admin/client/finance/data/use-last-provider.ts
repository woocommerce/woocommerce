/**
 * External dependencies
 */
import { useMemo, useState } from '@wordpress/element';
import { useUserPreferences } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import type { FinanceProvider } from '../types';

/**
 * Track the provider selected on the Payouts page, persisted per user. Falls back to the first
 * provider when nothing valid is stored; only an explicit selection is persisted.
 *
 * @param providers The providers the user can pick from.
 */
export function useLastProvider( providers: FinanceProvider[] ) {
	const { payments_finance_last_provider: storedId, updateUserPreferences } =
		useUserPreferences();
	const [ pickedId, setPickedId ] = useState< string | null >( null );

	const selectedId = useMemo( () => {
		const isKnown = ( id: string | null | undefined ): id is string =>
			!! id && providers.some( ( p ) => p.provider_id === id );

		if ( isKnown( pickedId ) ) {
			return pickedId;
		}
		if ( isKnown( storedId ) ) {
			return storedId;
		}
		return providers[ 0 ]?.provider_id ?? null;
	}, [ pickedId, storedId, providers ] );

	const selectProvider = ( id: string ) => {
		setPickedId( id );
		Promise.resolve(
			updateUserPreferences( { payments_finance_last_provider: id } )
		).catch( () => {
			// The selection still applies for this session even when it cannot be persisted.
		} );
	};

	return { selectedId, selectProvider };
}
