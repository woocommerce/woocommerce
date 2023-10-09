/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { WooFooterItem } from '@woocommerce/admin-layout';

/**
 * Internal dependencies
 */
import { FeedbackBar } from '../feedback-bar';
import { ProductMVPFeedbackModalContainer } from '../product-mvp-feedback-modal-container';

export type FooterProps = {
	productType: string;
	productId: number;
};

export function Footer( { productType, productId }: FooterProps ) {
	return (
		<WooFooterItem>
			<>
				<FeedbackBar productType={ productType } />
				<ProductMVPFeedbackModalContainer productId={ productId } />
			</>
		</WooFooterItem>
	);
}
