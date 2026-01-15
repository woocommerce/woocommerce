/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Disabled } from '@wordpress/components';
import { MultiLineTextSkeleton } from '@woocommerce/base-components/skeleton/patterns/multi-line-text-skeleton';

export const Skeleton = ( {
	buttonText,
	productType,
	isLoading = false,
}: {
	buttonText?: string | undefined;
	productType?: string | undefined;
	isLoading?: boolean;
} ) => {
	return (
		<div
			aria-label={
				isLoading
					? __(
							'Loading the Add to Cart + Options template part',
							'woocommerce'
					  )
					: __( 'Add to Cart + Options form', 'woocommerce' )
			}
		>
			<div className="wp-block-woocommerce-add-to-cart-with-options__skeleton-wrapper">
				<MultiLineTextSkeleton isStatic={ ! isLoading } />
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
		</div>
	);
};
