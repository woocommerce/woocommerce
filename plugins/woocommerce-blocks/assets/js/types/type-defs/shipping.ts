/**
 * External dependencies
 */
import type { ReactElement } from 'react';

export interface PackageRateOption {
	label: string;
	value: string;
	description?: string | ReactElement;
	secondaryLabel?: string | ReactElement;
	secondaryDescription?: string;
	id?: string;
}
