/**
 * External dependencies
 */
import type { Product } from '@woocommerce/data';
import type { DataFormControlProps } from '@wordpress/dataviews';

export type ProductDataFormControlProps<
	Attributes = Record< string, unknown >
> = DataFormControlProps< Product > & { attributes?: Attributes };
