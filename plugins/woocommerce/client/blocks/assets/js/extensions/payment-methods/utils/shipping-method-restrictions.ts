/**
 * External dependencies
 */
import type { CanMakePaymentArgument } from '@woocommerce/types';

/**
 * Settings exposed by offline payment methods that can be restricted to
 * selected shipping methods (see ShippingMethodRestrictionsTrait in PHP).
 */
export interface ShippingMethodRestrictionSettings {
	enableForVirtual?: boolean;
	enableForShippingMethods?: string[];
}

/**
 * Determine whether a payment method restricted by shipping method is
 * available for the current cart, mirroring the server-side check in
 * `ShippingMethodRestrictionsTrait::is_available()`.
 *
 * @param settings Payment method settings.
 * @return A `canMakePayment` callback for `registerPaymentMethod`.
 */
export const canMakePaymentForShippingMethods =
	( settings: ShippingMethodRestrictionSettings ) =>
	( {
		cartNeedsShipping,
		selectedShippingMethods,
	}: Pick<
		CanMakePaymentArgument,
		'cartNeedsShipping' | 'selectedShippingMethods'
	> ): boolean => {
		const enableForShippingMethods =
			settings.enableForShippingMethods ?? [];

		if ( settings.enableForVirtual && ! cartNeedsShipping ) {
			// Store allows the payment method for virtual orders.
			return true;
		}

		if ( ! enableForShippingMethods.length ) {
			// Store does not limit the payment method to specific shipping methods.
			return true;
		}

		// Look for a supported shipping method in the user's selected
		// shipping methods. If one is found, then the payment method is allowed.
		const selectedMethods = Object.values( selectedShippingMethods );

		// Enable until proven unavailable.
		if ( selectedMethods.length === 0 ) {
			return true;
		}

		// Supported shipping methods might be global (eg. "Any flat rate"), hence
		// this is doing a `String.prototype.includes` match vs a `Array.prototype.includes` match.
		return enableForShippingMethods.some( ( shippingMethodId ) =>
			selectedMethods.some(
				( selectedMethod ) =>
					typeof selectedMethod === 'string' &&
					selectedMethod.includes( shippingMethodId )
			)
		);
	};
