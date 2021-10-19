/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { CartCheckoutFeedbackPrompt } from '@woocommerce/editor-components/feedback-prompt';
import { InspectorControls } from '@wordpress/block-editor';
import {
	Disabled,
	PanelBody,
	ToggleControl,
	Notice,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import { CartCheckoutCompatibilityNotice } from '@woocommerce/editor-components/compatibility-notices';
import ViewSwitcher from '@woocommerce/editor-components/view-switcher';
import PageSelector from '@woocommerce/editor-components/page-selector';
import { CART_PAGE_ID } from '@woocommerce/block-settings';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';
import {
	EditorProvider,
	useEditorContext,
	CartProvider,
} from '@woocommerce/base-context';
import { createInterpolateElement, useRef } from '@wordpress/element';
import { getAdminLink, getSetting } from '@woocommerce/settings';
import { previewCart } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import Block from './block.js';
import EmptyCartEdit from './empty-cart-edit';
import './editor.scss';

const BlockSettings = ( { attributes, setAttributes } ) => {
	const {
		isShippingCalculatorEnabled,
		checkoutPageId,
		hasDarkControls,
		showRateAfterTaxName,
	} = attributes;
	const { currentPostId } = useEditorContext();
	const { current: savedCheckoutPageId } = useRef( checkoutPageId );
	return (
		<InspectorControls>
			{ currentPostId !== CART_PAGE_ID && (
				<Notice
					className="wc-block-cart__page-notice"
					isDismissible={ false }
					status="warning"
				>
					{ createInterpolateElement(
						__(
							'If you would like to use this block as your default cart you must update your <a>page settings in WooCommerce</a>.',
							'woocommerce'
						),
						{
							a: (
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
			{ getSetting( 'shippingEnabled', true ) && (
				<PanelBody
					title={ __(
						'Shipping rates',
						'woocommerce'
					) }
				>
					<ToggleControl
						label={ __(
							'Shipping calculator',
							'woocommerce'
						) }
						help={ __(
							'Allow customers to estimate shipping by entering their address.',
							'woocommerce'
						) }
						checked={ isShippingCalculatorEnabled }
						onChange={ () =>
							setAttributes( {
								isShippingCalculatorEnabled: ! isShippingCalculatorEnabled,
							} )
						}
					/>
				</PanelBody>
			) }
			{ getSetting( 'taxesEnabled' ) &&
				getSetting( 'displayItemizedTaxes', false ) &&
				! getSetting( 'displayCartPricesIncludingTax', false ) && (
					<PanelBody
						title={ __( 'Taxes', 'woocommerce' ) }
					>
						<ToggleControl
							label={ __(
								'Show rate after tax name',
								'woocommerce'
							) }
							help={ __(
								'Show the percentage rate alongside each tax line in the summary.',
								'woocommerce'
							) }
							checked={ showRateAfterTaxName }
							onChange={ () =>
								setAttributes( {
									showRateAfterTaxName: ! showRateAfterTaxName,
								} )
							}
						/>
					</PanelBody>
				) }
			{ ! (
				currentPostId === CART_PAGE_ID && savedCheckoutPageId === 0
			) && (
				<PageSelector
					pageId={ checkoutPageId }
					setPageId={ ( id ) =>
						setAttributes( { checkoutPageId: id } )
					}
					labels={ {
						title: __(
							'Proceed to Checkout button',
							'woocommerce'
						),
						default: __(
							'WooCommerce Checkout Page',
							'woocommerce'
						),
					} }
				/>
			) }
			<PanelBody title={ __( 'Style', 'woocommerce' ) }>
				<ToggleControl
					label={ __(
						'Dark mode inputs',
						'woocommerce'
					) }
					help={ __(
						'Inputs styled specifically for use on dark background colors.',
						'woocommerce'
					) }
					checked={ hasDarkControls }
					onChange={ () =>
						setAttributes( {
							hasDarkControls: ! hasDarkControls,
						} )
					}
				/>
			</PanelBody>
			<CartCheckoutFeedbackPrompt />
		</InspectorControls>
	);
};

/**
 * Component to handle edit mode of "Cart Block".
 *
 * Note: We need to always render `<InnerBlocks>` in the editor. Otherwise,
 *       if the user saves the page without having triggered the 'Empty Cart'
 *       view, inner blocks would not be saved and they wouldn't be visible
 *       in the frontend.
 *
 * @param {Object} props Incoming props for the component.
 * @param {string} props.className CSS class used.
 * @param {Object} props.attributes Attributes available.
 * @param {function(any):any} props.setAttributes Setter for attributes.
 */
const CartEditor = ( { className, attributes, setAttributes } ) => {
	return (
		<div
			className={ classnames( className, 'wp-block-woocommerce-cart', {
				'is-editor-preview': attributes.isPreview,
			} ) }
		>
			<ViewSwitcher
				label={ __( 'Edit', 'woocommerce' ) }
				views={ [
					{
						value: 'full',
						name: __( 'Full Cart', 'woocommerce' ),
					},
					{
						value: 'empty',
						name: __(
							'Empty Cart',
							'woocommerce'
						),
					},
				] }
				defaultView={ 'full' }
				render={ ( currentView ) => (
					<BlockErrorBoundary
						header={ __(
							'Cart Block Error',
							'woocommerce'
						) }
						text={ __(
							'There was an error whilst rendering the cart block. If this problem continues, try re-creating the block.',
							'woocommerce'
						) }
						showErrorMessage={ true }
						errorMessagePrefix={ __(
							'Error message:',
							'woocommerce'
						) }
					>
						{ currentView === 'full' && (
							<>
								<EditorProvider previewData={ { previewCart } }>
									<BlockSettings
										attributes={ attributes }
										setAttributes={ setAttributes }
									/>
									<Disabled>
										<CartProvider>
											<Block attributes={ attributes } />
										</CartProvider>
									</Disabled>
								</EditorProvider>
								<EmptyCartEdit hidden={ true } />
							</>
						) }
						{ currentView === 'empty' && <EmptyCartEdit /> }
					</BlockErrorBoundary>
				) }
			/>
			<CartCheckoutCompatibilityNotice blockName="cart" />
		</div>
	);
};

CartEditor.propTypes = {
	className: PropTypes.string,
};

export default CartEditor;
