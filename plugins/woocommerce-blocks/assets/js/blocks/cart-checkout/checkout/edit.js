/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { withFeedbackPrompt } from '@woocommerce/block-hocs';
import { previewShippingRates } from '@woocommerce/resource-previews';
import { SHIPPING_METHODS_EXIST } from '@woocommerce/block-settings';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	CheckboxControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block.js';
import './editor.scss';

const CheckoutEditor = ( { attributes, setAttributes } ) => {
	const {
		className,
		useShippingAsBilling,
		showCompanyField,
		showAddress2Field,
		showPhoneField,
		requireCompanyField,
		requirePhoneField,
	} = attributes;
	// @todo: wrap Block with Disabled once you finish building the form
	return (
		<div className={ className }>
			<InspectorControls>
				<PanelBody
					title={ __(
						'Form options',
						'woo-gutenberg-products-block'
					) }
				>
					<p className="wc-block-checkout__controls-text">
						{ __(
							'Choose whether your checkout form requires extra information from customers.',
							'woo-gutenberg-products-block'
						) }
					</p>
					<ToggleControl
						label={ __(
							'Company name',
							'woo-gutenberg-products-block'
						) }
						checked={ showCompanyField }
						onChange={ () =>
							setAttributes( {
								showCompanyField: ! showCompanyField,
							} )
						}
					/>
					{ showCompanyField && (
						<CheckboxControl
							label={ __(
								'Require company name?',
								'woo-gutenberg-products-block'
							) }
							checked={ requireCompanyField }
							onChange={ () =>
								setAttributes( {
									requireCompanyField: ! requireCompanyField,
								} )
							}
							className="components-base-control--nested"
						/>
					) }
					<ToggleControl
						label={ __(
							'Apartment, suite, unit etc',
							'woo-gutenberg-products-block'
						) }
						checked={ showAddress2Field }
						onChange={ () =>
							setAttributes( {
								showAddress2Field: ! showAddress2Field,
							} )
						}
					/>
					<ToggleControl
						label={ __(
							'Phone number',
							'woo-gutenberg-products-block'
						) }
						checked={ showPhoneField }
						onChange={ () =>
							setAttributes( {
								showPhoneField: ! showPhoneField,
							} )
						}
					/>
					{ showPhoneField && (
						<CheckboxControl
							label={ __(
								'Require phone number?',
								'woo-gutenberg-products-block'
							) }
							checked={ requirePhoneField }
							onChange={ () =>
								setAttributes( {
									requirePhoneField: ! requirePhoneField,
								} )
							}
							className="components-base-control--nested"
						/>
					) }
				</PanelBody>
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
