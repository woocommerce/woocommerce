/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

const Edit = () => {
	const blockProps = useBlockProps( {
		className: 'wc-block-product-collection-saved-item',
	} );

	return (
		<div { ...blockProps }>
			<p className="wc-block-product-collection-saved-item__variation">
				{ __( 'Size: M', 'woocommerce' ) }
			</p>
			<p className="wc-block-product-collection-saved-item__quantity">
				{ __( 'Qty: 1', 'woocommerce' ) }
			</p>
			<button
				type="button"
				className="wc-block-product-collection-saved-item__move-to-cart wp-element-button"
				disabled
			>
				{ __( 'Move to cart', 'woocommerce' ) }
			</button>
			<button
				type="button"
				className="wc-block-product-collection-saved-item__remove"
				disabled
			>
				{ __( 'Remove', 'woocommerce' ) }
			</button>
		</div>
	);
};

export default Edit;
