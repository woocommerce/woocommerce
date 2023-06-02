/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { WooFooterItem } from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import { ProductMVPCESFooter } from '../product-mvp-ces-footer';
import { ProductMVPFeedbackModalContainer } from '../product-mvp-feedback-modal-container';

export type FooterProps = {
	productId: number;
};

export function Footer( { productId }: FooterProps ) {
	return (
		<div
			className="woocommerce-product-footer"
			role="region"
			aria-label={ __( 'Product Editor bottom bar.', 'woocommerce' ) }
			tabIndex={ -1 }
		>
			<WooFooterItem.Slot name="product" />

			<ProductMVPCESFooter />
			<ProductMVPFeedbackModalContainer productId={ productId } />
		</div>
	);
}
