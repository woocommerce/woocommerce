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
import withProductSelector from '../shared/with-product-selector';
import { BLOCK_TITLE, BLOCK_ICON } from './constants';

const Edit = ( { attributes, setAttributes } ) => {
	const { product } = useProductDataContext();
	const { className, showFormElements } = attributes;

	return (
		<div
			className={ classnames(
				className,
				'wc-block-components-product-add-to-cart'
			) }
		>
			<EditProductLink productId={ product.id } />
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

export default withProductSelector( {
	icon: BLOCK_ICON,
	label: BLOCK_TITLE,
	description: __(
		"Choose a product to display it's add to cart form.",
		'woocommerce'
	),
} )( Edit );
