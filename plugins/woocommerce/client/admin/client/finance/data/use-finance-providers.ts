/**
 * Internal dependencies
 */
import { getProvidersPath } from './api';
import { useApiRequest } from './use-api-request';
import type { FinanceProvider, FinanceProvidersResponse } from '../types';

const NO_PROVIDERS: FinanceProvider[] = [];

export function useFinanceProviders() {
	const { data, isLoading, error, refetch } =
		useApiRequest< FinanceProvidersResponse >( getProvidersPath() );

	return {
		providers: data?.providers ?? NO_PROVIDERS,
		isLoading,
		error,
		refetch,
	};
}
