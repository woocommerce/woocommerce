/**
 * Internal dependencies
 */
import { getBalancesPath } from './api';
import { useApiRequest } from './use-api-request';
import type { Balance, FinancePage } from '../types';

const NO_BALANCES: Balance[] = [];

/**
 * Fetch the balances of a provider. Pass null to skip fetching.
 *
 * @param providerId The provider id, or null.
 */
export function useProviderBalances( providerId: string | null ) {
	const { data, isLoading, error, refetch } = useApiRequest<
		FinancePage< Balance >
	>( providerId ? getBalancesPath( providerId ) : null );

	return {
		balances: data?.items ?? NO_BALANCES,
		isLoading,
		error,
		refetch,
	};
}
