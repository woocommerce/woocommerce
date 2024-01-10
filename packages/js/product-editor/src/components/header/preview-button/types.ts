/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { ButtonProps } from '@wordpress/components/build-types/button/types';

export type PreviewButtonProps = Omit<
	ButtonProps,
	'aria-disabled' | 'variant' | 'href' | 'children'
> & {
	productStatus: Product[ 'status' ];
	productType: string;
};
