/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { withFeedbackPrompt } from '@woocommerce/block-hocs';
import {
	previewCart,
	previewShippingRates,
} from '@woocommerce/resource-previews';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	CheckboxControl,
	Notice,
} from '@wordpress/components';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';
import {
	PRIVACY_URL,
	TERMS_URL,
	SHIPPING_METHODS_EXIST,
} from '@woocommerce/block-settings';
import { getAdminLink } from '@woocommerce/settings';
import { __experimentalCreateInterpolateElement } from 'wordpress-element';

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
		showPolicyLinks,
	} = attributes;
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
							'Company',
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
							'Apartment, suite, etc.',
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
						label={ __( 'Phone', 'woo-gutenberg-products-block' ) }
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
				<PanelBody
					title={ __( 'Content', 'woo-gutenberg-products-block' ) }
				>
					<p className="wc-block-checkout__controls-text">
						{ __(
							'Choose additional content to display on checkout.',
							'woo-gutenberg-products-block'
						) }
					</p>
					<ToggleControl
						label={ __(
							'Show links to terms and conditions and privacy policy',
							'woo-gutenberg-products-block'
						) }
						checked={ showPolicyLinks }
						onChange={ () =>
							setAttributes( {
								showPolicyLinks: ! showPolicyLinks,
							} )
						}
					/>
					{ showPolicyLinks && ( ! PRIVACY_URL || ! TERMS_URL ) && (
						<Notice
							className="wc-block-base-control-notice"
							isDismissible={ false }
						>
							{ __experimentalCreateInterpolateElement(
								__(
									'Pages must be first setup in store settings: <a1>Privacy policy</a1>, <a2>Terms and conditions</a2>.',
									'woo-gutenberg-products-block'
								),
								{
									a1: (
										// eslint-disable-next-line jsx-a11y/anchor-has-content
										<a
											href={ getAdminLink(
												'admin.php?page=wc-settings&tab=account'
											) }
											target="_blank"
											rel="noopener noreferrer"
										/>
									),
									a2: (
										// eslint-disable-next-line jsx-a11y/anchor-has-content
										<a
											href={ getAdminLink(
												'admin.php?page=wc-settings&tab=advanced'
											) }
											target="_blank"
											rel="noopener noreferrer"
										/>
									),
								}
							) }
						</Notice>
					) }
				</PanelBody>
			</InspectorControls>
			<BlockErrorBoundary
				header={ __(
					'Checkout Block Error',
					'woo-gutenberg-products-block'
				) }
				text={ __(
					'There was an error whilst rendering the checkout block. If this problem continues, try re-creating the block.',
					'woo-gutenberg-products-block'
				) }
				showErrorMessage={ true }
				errorMessagePrefix={ __(
					'Error message:',
					'woo-gutenberg-products-block'
				) }
			>
				<Block
					attributes={ attributes }
					cartItems={ previewCart.items }
					cartTotals={ previewCart.totals }
					isEditor={ true }
					shippingRates={
						SHIPPING_METHODS_EXIST ? previewShippingRates : []
					}
				/>
			</BlockErrorBoundary>
		</div>
	);
};

export default withFeedbackPrompt(
	__(
		'We are currently working on improving our cart and checkout blocks, providing merchants with the tools and customization options they need.',
		'woo-gutenberg-products-block'
	)
)( CheckoutEditor );
