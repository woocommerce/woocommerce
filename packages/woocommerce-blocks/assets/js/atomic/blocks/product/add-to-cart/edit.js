/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import EditProductLink from '@woocommerce/block-components/edit-product-link';
import { useProductDataContext } from '@woocommerce/shared-context';
import classnames from 'classnames';
import { Disabled, PanelBody, ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './style.scss';
import Block from './block';

export default ( { attributes, setAttributes } ) => {
	const productDataContext = useProductDataContext();
	const product = productDataContext.product || {};
	const { className, showFormElements } = attributes;

	return (
		<div
			className={ classnames(
				className,
				'wc-block-components-product-add-to-cart'
			) }
		>
			<EditProductLink productId={ product.id || 0 } />
			{ product.type !== 'external' && (
				<InspectorControls>
					<PanelBody
						title={ __( 'Layout', 'woocommerce' ) }
					>
						<ToggleControl
							label={ __(
								'Display form elements',
								'woocommerce'
							) }
							help={ __(
								'Depending on product type, allow customers to select a quantity, variations etc.',
								'woocommerce'
							) }
							checked={ showFormElements }
							onChange={ () =>
								setAttributes( {
									showFormElements: ! showFormElements,
								} )
							}
						/>
					</PanelBody>
				</InspectorControls>
			) }
			<Disabled>
				<Block { ...attributes } />
			</Disabled>
		</div>
	);
};
