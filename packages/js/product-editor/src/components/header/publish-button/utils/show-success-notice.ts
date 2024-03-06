/**
 * External dependencies
 */
import { dispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import type { Product, ProductStatus } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { formatScheduleDatetime } from '../../../../utils';

function getNoticeContent( product: Product, prevStatus?: ProductStatus ) {
	if (
		window.wcAdminFeatures[ 'product-pre-publish-modal' ] &&
		product.status === 'future'
	) {
		return sprintf(
			// translators: %s: The datetime the product is scheduled for.
			__( 'Product scheduled for %s.', 'woocommerce' ),
			formatScheduleDatetime( `${ product.date_created_gmt }+00:00` )
		);
	}

	if ( prevStatus === 'publish' || prevStatus === 'future' ) {
		return __( 'Product updated.', 'woocommerce' );
	}

	return __( 'Product published.', 'woocommerce' );
}

export function showSuccessNotice(
	product: Product,
	prevStatus?: ProductStatus
) {
	const { createSuccessNotice } = dispatch( 'core/notices' );

	const noticeContent = getNoticeContent( product, prevStatus );
	const noticeOptions = {
		icon: '🎉',
		actions: [
			{
				label: __( 'View in store', 'woocommerce' ),
				// Leave the url to support a11y.
				url: product.permalink,
				onClick( event: React.MouseEvent< HTMLAnchorElement > ) {
					event.preventDefault();
					// Notice actions do not support target anchor prop,
					// so this forces the page to be opened in a new tab.
					window.open( product.permalink, '_blank' );
				},
			},
		],
	};

	createSuccessNotice( noticeContent, noticeOptions );
}
