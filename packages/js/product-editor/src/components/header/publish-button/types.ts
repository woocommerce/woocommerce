/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { Button } from '@wordpress/components';

export type PublishButtonProps = Omit<
	Button.ButtonProps,
	'aria-disabled' | 'variant' | 'children'
> & {
	productStatus: Product[ 'status' ];
	productType?: string;
};
