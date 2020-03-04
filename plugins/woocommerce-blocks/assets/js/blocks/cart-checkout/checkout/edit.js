/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { withFeedbackPrompt } from '@woocommerce/block-hocs';
import { previewShippingRates } from '@woocommerce/resource-previews';
import { SHIPPING_METHODS_EXIST } from '@woocommerce/block-settings';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block.js';
import './editor.scss';

const CheckoutEditor = ( { attributes, setAttributes } ) => {
	const { className, useShippingAsBilling } = attributes;
	// @todo: wrap Block with Disabled once you finish building the form
	return (
		<div className={ className }>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Billing address',
						'woo-gutenberg-products-block'
					) }
				>
					<p className="wc-block-checkout__controls-text">
						{ __(
							'Reduce the number of fields required to checkout.',
							'woo-gutenberg-products-block'
						) }
					</p>
					<ToggleControl
						label={ __(
							'Use the shipping address as the billing address',
							'woo-gutenberg-products-block'
						) }
						checked={ useShippingAsBilling }
						onChange={ () =>
							setAttributes( {
								useShippingAsBilling: ! useShippingAsBilling,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<Block
				attributes={ attributes }
				isEditor={ true }
				shippingRates={
					SHIPPING_METHODS_EXIST ? previewShippingRates : []
				}
			/>
		</div>
	);
};

export default withFeedbackPrompt(
	__(
		'We are currently working on improving our checkout and providing merchants with tools and options to customize their checkout to their stores needs.',
		'woo-gutenberg-products-block'
	)
)( CheckoutEditor );
