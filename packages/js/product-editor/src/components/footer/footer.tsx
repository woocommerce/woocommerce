/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { WooFooterItem } from '@woocommerce/admin-layout';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { FeedbackBar } from '../feedback-bar';
import { ProductMVPFeedbackModalContainer } from '../product-mvp-feedback-modal-container';

export type FooterProps = {
	product: Partial< Product >;
};

export function Footer( { product }: FooterProps ) {
	return (
		<div
			className="woocommerce-product-footer"
			role="region"
			aria-label={ __( 'Product Editor bottom bar.', 'woocommerce' ) }
			tabIndex={ -1 }
		>
			<WooFooterItem.Slot name="product" />

			<FeedbackBar product={ product } />
			<ProductMVPFeedbackModalContainer productId={ product.id } />
		</div>
	);
}
