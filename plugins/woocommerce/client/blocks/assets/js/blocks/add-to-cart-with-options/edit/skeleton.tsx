/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled } from '@wordpress/components';
import { MultiLineTextSkeleton } from '@woocommerce/base-components/skeleton/patterns/multi-line-text-skeleton';

export const Skeleton = ( {
	buttonText,
	productType,
	isStatic = false,
}: {
	buttonText?: string | undefined;
	productType?: string | undefined;
	isStatic?: boolean;
} ) => {
	return (
		<>
			<div className="wp-block-woocommerce-add-to-cart-with-options__skeleton-wrapper">
				<MultiLineTextSkeleton isStatic={ isStatic } />
			</div>
			<Disabled>
				<button
					className={ `alt wp-element-button ${
						productType || 'simple'
					}_add_to_cart_button` }
				>
					{ buttonText || __( 'Add to cart', 'woocommerce' ) }
				</button>
			</Disabled>
		</>
	);
};
