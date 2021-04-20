/**
 * External dependencies
 */
import { triggerFragmentRefresh } from '@woocommerce/base-utils';

/**
 * Default export for registering the controls with the store.
 *
 * @return {Object} An object with the controls to register with the store on the controls property of the registration object.
 */
export const controls = {
	UPDATE_LEGACY_CART_FRAGMENTS() {
		triggerFragmentRefresh();
	},
};
