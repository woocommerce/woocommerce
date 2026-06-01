/**
 * Internal dependencies
 */
import {
	DEFAULT_TITLE,
	DEFAULT_DESCRIPTION,
	DEFAULT_FORCED_BILLING_DESCRIPTION,
	DEFAULT_FORCED_BILLING_TITLE,
} from './constants';

export const getBillingAddresssBlockTitle = (
	title: string,
	forcedBillingAddress: boolean,
	isLocalPickup: boolean
): string => {
	if ( forcedBillingAddress && ! isLocalPickup ) {
		// Returns the combined "Billing and shipping address" title only if forced billing is enabled and Local Pickup is not selected, and no custom title is set.
		return title === DEFAULT_TITLE ? DEFAULT_FORCED_BILLING_TITLE : title;
	}
	// Returns default title when forced billing address is disabled and there is no title set.
	return title === DEFAULT_FORCED_BILLING_TITLE ? DEFAULT_TITLE : title;
};

export const getBillingAddresssBlockDescription = (
	description: string,
	forcedBillingAddress: boolean,
	isLocalPickup: boolean
): string => {
	if ( forcedBillingAddress && ! isLocalPickup ) {
		// Returns the combined "Billing and shipping address" description if forced billing is enabled, Local Pickup is not selected, and the default description is used.
		return description === DEFAULT_DESCRIPTION
			? DEFAULT_FORCED_BILLING_DESCRIPTION
			: description;
	}

	// Returns default description when forced billing address is disabled and there is no description set.
	return description === DEFAULT_FORCED_BILLING_DESCRIPTION
		? DEFAULT_DESCRIPTION
		: description;
};
