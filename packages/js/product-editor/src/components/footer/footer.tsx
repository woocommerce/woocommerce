/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
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
		<WooFooterItem>
			<>
				<FeedbackBar product={ product } />
				<ProductMVPFeedbackModalContainer productId={ product.id } />
			</>
		</WooFooterItem>
	);
}
