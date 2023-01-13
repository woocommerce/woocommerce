/**
 * External dependencies
 */
import type { ReactElement } from 'react';

export interface PackageRateOption {
	label: string;
	value: string;
	description?: string | ReactElement | undefined;
	secondaryLabel?: string | ReactElement | undefined;
	secondaryDescription?: string | ReactElement | undefined;
	id?: string | undefined;
}

export interface SelectShippingRateType {
	// Returns a function that accepts a shipping rate ID and a package ID.
	selectShippingRate: (
		newShippingRateId: string,
		packageId?: string | number
	) => unknown;
	// True when a rate is currently being selected and persisted to the server.
	isSelectingRate: boolean;
}
