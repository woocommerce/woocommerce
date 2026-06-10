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

	window.wcAdminFeatures = new Proxy( window.wcAdminFeatures, {
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

	Object.defineProperty(
		window.wcAdminFeatures,
		WC_ADMIN_FEATURES_PROXY_MARKER,
		{
			value: true,
		}
	);
}

applyRetiredFeatureFlagDeprecationProxy();

function getWcAdminFeatureValue( featureId: string ): boolean | undefined {
	if ( typeof window === 'undefined' || ! window.wcAdminFeatures ) {
		return undefined;
	}

	const feature = Object.getOwnPropertyDescriptor(
		window.wcAdminFeatures,
		featureId
	);

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
