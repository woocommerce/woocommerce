/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useWooBlockProps } from '@woocommerce/block-templates';

/**
 * Internal dependencies
 */
import { ProductEditorBlockEditProps } from '../../../types';
import { Shirt, Pants, Glasses } from './images';
import { UploadsBlockAttributes } from './types';

export function Edit( {
	attributes,
}: ProductEditorBlockEditProps< UploadsBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );

	return (
		<div { ...blockProps }>
			<div className="wp-block-woocommerce-product-list-field__body">
				<div className="wp-block-woocommerce-product-list-field__empty-state">
					<div
						className="wp-block-woocommerce-product-list-field__empty-state-illustration"
						role="presentation"
					>
						<Shirt />
						<Pants />
						<Glasses />
					</div>
					<p className="wp-block-woocommerce-product-list-field__empty-state-tip">
						{ __(
							'Tip: Group together items that have a clear relationship or compliment each other well, e.g., garment bundles, camera kits, or skincare product sets.',
							'woocommerce'
						) }
					</p>
				</div>
			</div>
		</div>
	);
}
