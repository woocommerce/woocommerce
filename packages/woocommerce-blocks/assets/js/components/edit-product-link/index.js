/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, external } from '@woocommerce/icons';
import { ADMIN_URL } from '@woocommerce/settings';
import { InspectorControls } from '@wordpress/block-editor';

/**
 * Component to render an edit product link in the sidebar.
 */
const EditProductLink = ( { productId } ) => {
	return (
		<InspectorControls>
			<div className="wc-block-single-product__edit-card">
				<div className="wc-block-single-product__edit-card-title">
					<a
						href={ `${ ADMIN_URL }post.php?post=${ productId }&action=edit` }
						target="_blank"
						rel="noopener noreferrer"
					>
						{ __(
							"Edit this product's details",
							'woocommerce'
						) }
						<Icon srcElement={ external } size={ 16 } />
					</a>
				</div>
				<div className="wc-block-single-product__edit-card-description">
					{ __(
						'Edit details such as title, price, description and more.',
						'woocommerce'
					) }
				</div>
			</div>
		</InspectorControls>
	);
};

export default EditProductLink;
