/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { CartCheckoutFeedbackPrompt } from '@woocommerce/editor-components/feedback-prompt';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	CheckboxControl,
	Notice,
	Disabled,
} from '@wordpress/components';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';
import {
	PRIVACY_URL,
	TERMS_URL,
	CHECKOUT_PAGE_ID,
	CHECKOUT_ALLOWS_SIGNUP,
} from '@woocommerce/block-settings';
import { compareWithWooVersion, getAdminLink } from '@woocommerce/settings';
import { createInterpolateElement } from 'wordpress-element';
import { useRef } from '@wordpress/element';
import {
	EditorProvider,
	useEditorContext,
	StoreNoticesProvider,
} from '@woocommerce/base-context';
import PageSelector from '@woocommerce/editor-components/page-selector';
import {
	previewCart,
	previewSavedPaymentMethods,
} from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import Block from './block.js';
import './editor.scss';

const BlockSettings = ( { attributes, setAttributes } ) => {
	const {
		showCompanyField,
		showApartmentField,
		showPhoneField,
		requireCompanyField,
		requirePhoneField,
		allowCreateAccount,
		showOrderNotes,
		showPolicyLinks,
		showReturnToCart,
		cartPageId,
		hasDarkControls,
	} = attributes;
	const { currentPostId } = useEditorContext();
	const { current: savedCartPageId } = useRef( cartPageId );
	// Checkout signup is feature gated to WooCommerce 4.7 and newer;
	// uses updated my-account/lost-password screen from 4.7+ for
	// setting initial password.
	// Also implicitly gated to feature plugin, because Checkout
	// block is gated to plugin
	const showCreateAccountOption =
		CHECKOUT_ALLOWS_SIGNUP && compareWithWooVersion( '4.7.0', '<=' );
	return (
		<InspectorControls>
			{ currentPostId !== CHECKOUT_PAGE_ID && (
				<Notice
					className="wc-block-checkout__page-notice"
					isDismissible={ false }
					status="warning"
				>
					{ createInterpolateElement(
						__(
							'If you would like to use this block as your default checkout you must update your <a>page settings in WooCommerce</a>.',
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
			<PanelBody
				title={ __(
					'Address options',
					'woocommerce'
				) }
			>
				<p className="wc-block-checkout__controls-text">
					{ __(
						'Include additional address fields in the checkout form.',
						'woocommerce'
					) }
				</p>
				<ToggleControl
					label={ __( 'Company', 'woocommerce' ) }
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
							'woocommerce'
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
						'woocommerce'
					) }
					checked={ showApartmentField }
					onChange={ () =>
						setAttributes( {
							showApartmentField: ! showApartmentField,
						} )
					}
				/>
				<ToggleControl
					label={ __( 'Phone', 'woocommerce' ) }
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
							'woocommerce'
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
			{ showCreateAccountOption && (
				<PanelBody
					title={ __(
						'Account options',
						'woocommerce'
					) }
				>
					<ToggleControl
						label={ __(
							'Allow shoppers to sign up for a user account during checkout',
							'woocommerce'
						) }
						checked={ allowCreateAccount }
						onChange={ () =>
							setAttributes( {
								allowCreateAccount: ! allowCreateAccount,
							} )
						}
					/>
				</PanelBody>
			) }
			<PanelBody
				title={ __( 'Order notes', 'woocommerce' ) }
			>
				<p className="wc-block-checkout__controls-text">
					{ __(
						'Reduce the number of fields to checkout.',
						'woocommerce'
					) }
				</p>
				<ToggleControl
					label={ __(
						'Allow shoppers to optionally add order notes',
						'woocommerce'
					) }
					checked={ showOrderNotes }
					onChange={ () =>
						setAttributes( {
							showOrderNotes: ! showOrderNotes,
						} )
					}
				/>
			</PanelBody>
			<PanelBody
				title={ __(
					'Navigation options',
					'woocommerce'
				) }
			>
				<ToggleControl
					label={ __(
						'Show links to policies',
						'woocommerce'
					) }
					help={ __(
						'Shows links to your "terms and conditions" and "privacy policy" pages.',
						'woocommerce'
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
						{ createInterpolateElement(
							__(
								'Pages must be first setup in store settings: <a1>Privacy policy</a1>, <a2>Terms and conditions</a2>.',
								'woocommerce'
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
				<ToggleControl
					label={ __(
						'Show a "Return to Cart" link',
						'woocommerce'
					) }
					checked={ showReturnToCart }
					onChange={ () =>
						setAttributes( {
							showReturnToCart: ! showReturnToCart,
						} )
					}
				/>
			</PanelBody>
			{ showReturnToCart &&
				! (
					currentPostId === CHECKOUT_PAGE_ID && savedCartPageId === 0
				) && (
					<PageSelector
						pageId={ cartPageId }
						setPageId={ ( id ) =>
							setAttributes( { cartPageId: id } )
						}
						labels={ {
							title: __(
								'Return to Cart button',
								'woocommerce'
							),
							default: __(
								'WooCommerce Cart Page',
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

const CheckoutEditor = ( { attributes, setAttributes } ) => {
	const { className, isPreview } = attributes;
	return (
		<EditorProvider
			previewData={ { previewCart, previewSavedPaymentMethods } }
		>
			<div
				className={ classnames(
					className,
					'wp-block-woocommerce-checkout',
					{
						'is-editor-preview': isPreview,
					}
				) }
			>
				<BlockSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
				<BlockErrorBoundary
					header={ __(
						'Checkout Block Error',
						'woocommerce'
					) }
					text={ __(
						'There was an error whilst rendering the checkout block. If this problem continues, try re-creating the block.',
						'woocommerce'
					) }
					showErrorMessage={ true }
					errorMessagePrefix={ __(
						'Error message:',
						'woocommerce'
					) }
				>
					<StoreNoticesProvider context="wc/checkout">
						<Disabled>
							<Block attributes={ attributes } />
						</Disabled>
					</StoreNoticesProvider>
				</BlockErrorBoundary>
			</div>
		</EditorProvider>
	);
};

export default CheckoutEditor;
