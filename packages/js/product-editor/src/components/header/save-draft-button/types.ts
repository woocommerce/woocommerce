/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { ButtonProps } from '@wordpress/components/build-types/button/types';

export type SaveDraftButtonProps = Omit<
	ButtonProps,
	'aria-disabled' | 'variant' | 'children'
> & {
	productStatus: Product[ 'status' ];
	productType?: string;
};
