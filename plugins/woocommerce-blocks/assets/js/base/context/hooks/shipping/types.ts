/**
 * External dependencies
 */
import { Cart } from '@woocommerce/type-defs/cart';
import { SelectShippingRateType } from '@woocommerce/type-defs/shipping';

export interface ShippingData extends SelectShippingRateType {
	needsShipping: Cart[ 'needsShipping' ];
	hasCalculatedShipping: Cart[ 'hasCalculatedShipping' ];
	shippingRates: Cart[ 'shippingRates' ];
	isLoadingRates: boolean;
	selectedRates: Record< string, string | unknown >;

	/**
	 * The following values are used to determine if pickup methods are shown separately from shipping methods, or if
	 * those options should be hidden.
	 */

	// Only true when ALL packages support local pickup. If true, we can show the collection/delivery toggle
	isCollectable: boolean;

	// True when at least one package has selected local pickup
	hasSelectedLocalPickup: boolean;
}
