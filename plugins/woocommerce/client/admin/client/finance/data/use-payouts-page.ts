/**
 * Internal dependencies
 */
import { getPayoutsPath, PayoutsPageQuery } from './api';
import { useApiRequest } from './use-api-request';
import type { FinancePage, Payout } from '../types';

/**
 * Fetch one page of payouts of a provider. Pass a null provider to skip fetching.
 *
 * @param providerId The provider id, or null.
 * @param query      The cursor and page size.
 */
export function usePayoutsPage(
	providerId: string | null,
	query: PayoutsPageQuery
) {
	const { data, isLoading, error, refetch } = useApiRequest<
		FinancePage< Payout >
	>( providerId ? getPayoutsPath( providerId, query ) : null );

	return { page: data, isLoading, error, refetch };
}
