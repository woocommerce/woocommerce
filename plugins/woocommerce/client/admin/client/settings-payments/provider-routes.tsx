/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import type { ReactNode } from 'react';

export const SETTINGS_PAYMENTS_PROVIDER_ROUTES_FILTER =
	'woocommerce_admin_settings_payments_provider_routes';

export interface SettingsPaymentsProviderRoute {
	id: string;
	path: string;
	element: ReactNode;
	order?: number;
}

let registeredRoutes: SettingsPaymentsProviderRoute[] = [];

const normalizeRoute = (
	route: SettingsPaymentsProviderRoute
): SettingsPaymentsProviderRoute => ( {
	...route,
	path: route.path.startsWith( '/' ) ? route.path : `/${ route.path }`,
} );

const sortRoutes = (
	routes: SettingsPaymentsProviderRoute[]
): SettingsPaymentsProviderRoute[] =>
	[ ...routes ].sort( ( a, b ) => {
		const orderDifference = ( a.order ?? 0 ) - ( b.order ?? 0 );

		return orderDifference || a.id.localeCompare( b.id );
	} );

const assertUniquePaths = ( routes: SettingsPaymentsProviderRoute[] ) => {
	const seenPaths = new Set< string >();

	routes.forEach( ( route ) => {
		if ( seenPaths.has( route.path ) ) {
			throw new Error(
				`Duplicate Payments settings provider route path registered: ${ route.path }`
			);
		}

		seenPaths.add( route.path );
	} );
};

export const registerSettingsPaymentsProviderRoute = (
	route: SettingsPaymentsProviderRoute
): void => {
	registeredRoutes = [ ...registeredRoutes, normalizeRoute( route ) ];
};

export const getSettingsPaymentsProviderRoutes =
	(): SettingsPaymentsProviderRoute[] => {
		const routes = applyFilters(
			SETTINGS_PAYMENTS_PROVIDER_ROUTES_FILTER,
			registeredRoutes.map( normalizeRoute )
		) as SettingsPaymentsProviderRoute[];
		const normalizedRoutes = sortRoutes( routes.map( normalizeRoute ) );

		assertUniquePaths( normalizedRoutes );

		return normalizedRoutes;
	};

export const resetSettingsPaymentsProviderRoutesForTesting = (): void => {
	registeredRoutes = [];
};
