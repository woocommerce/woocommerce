/**
 * Internal dependencies
 */
import { getAdminSetting } from '../admin-settings';
import { Feature } from './types';
import {
	isRetiredFeatureFlag,
	warnRetiredFeatureFlag,
} from './retired-feature-flags';

const ADMIN_SETTINGS_FEATURES_NAME = 'features';
const WC_ADMIN_FEATURES_PROXY_MARKER = '__wcRetiredFeatureFlagsProxy';
const proxiedFeatureFlagSources = new WeakMap<
	Record< string, unknown >,
	Record< string, unknown >
>();

function createRetiredFeatureFlagsProxy< T extends Record< string, unknown > >(
	features: T
): T {
	const proxy = new Proxy( features, {
		get( target, property, receiver ) {
			if (
				typeof property === 'string' &&
				isRetiredFeatureFlag( property )
			) {
				warnRetiredFeatureFlag( property );
			}

			return Reflect.get( target, property, receiver );
		},
	} );

	Object.defineProperty( proxy, WC_ADMIN_FEATURES_PROXY_MARKER, {
		value: true,
	} );
	proxiedFeatureFlagSources.set( proxy, features );

	return proxy as T;
}

export function applyRetiredFeatureFlagDeprecationProxy(): void {
	if (
		typeof window === 'undefined' ||
		process.env.NODE_ENV !== 'development' ||
		! window.wcAdminFeatures ||
		Boolean(
			( window.wcAdminFeatures as Record< string, boolean > )[
				WC_ADMIN_FEATURES_PROXY_MARKER
			]
		)
	) {
		return;
	}

	window.wcAdminFeatures = createRetiredFeatureFlagsProxy(
		window.wcAdminFeatures
	);
}

applyRetiredFeatureFlagDeprecationProxy();

function getWcAdminFeatureValue( featureId: string ): boolean | undefined {
	if ( typeof window === 'undefined' || ! window.wcAdminFeatures ) {
		return undefined;
	}

	const features =
		proxiedFeatureFlagSources.get(
			window.wcAdminFeatures as Record< string, unknown >
		) ?? window.wcAdminFeatures;
	const feature = Object.getOwnPropertyDescriptor( features, featureId );

	return feature ? Boolean( feature.value ) : undefined;
}

/**
 * Get the feature flag from admin settings.
 *
 * @param featureId The feature id
 * @return The feature flag
 */
export function getFeature( featureId: string ): Feature | undefined {
	const features = getAdminSetting( ADMIN_SETTINGS_FEATURES_NAME );
	return features ? features[ featureId ] : undefined;
}

/**
 * Returns if the feature is enabled.
 *
 * @param featureId The feature id
 * @return `true` or `false` if the given feature is enabled
 */
export function isFeatureEnabled( featureId: string ): boolean {
	if ( isRetiredFeatureFlag( featureId ) ) {
		const wcAdminFeatureValue = getWcAdminFeatureValue( featureId );

		if ( wcAdminFeatureValue !== undefined ) {
			return wcAdminFeatureValue;
		}

		warnRetiredFeatureFlag( featureId );
		return true;
	}

	const feature = getFeature( featureId );
	return Boolean( feature?.is_enabled );
}

/**
 * Returns if the feature is experimental.
 *
 * @param featureId The feature id
 * @return `true` or `false` if the given feature is experimental
 */
export function isFeatureExperimental( featureId: string ): boolean {
	const feature = getFeature( featureId );
	return Boolean( feature?.is_experimental );
}
