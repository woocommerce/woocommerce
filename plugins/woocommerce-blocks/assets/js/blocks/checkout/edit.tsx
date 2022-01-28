/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import {
	InnerBlocks,
	useBlockProps,
	InspectorControls,
} from '@wordpress/block-editor';
import { SidebarLayout } from '@woocommerce/base-components/sidebar-layout';
import {
	CheckoutProvider,
	EditorProvider,
	useEditorContext,
} from '@woocommerce/base-context';
import {
	previewCart,
	previewSavedPaymentMethods,
} from '@woocommerce/resource-previews';
import {
	PanelBody,
	ToggleControl,
	Notice,
	CheckboxControl,
} from '@wordpress/components';
import { CartCheckoutFeedbackPrompt } from '@woocommerce/editor-components/feedback-prompt';
import { CHECKOUT_PAGE_ID } from '@woocommerce/block-settings';
import { createInterpolateElement } from '@wordpress/element';
import { getAdminLink } from '@woocommerce/settings';
import { CartCheckoutCompatibilityNotice } from '@woocommerce/editor-components/compatibility-notices';
import type { TemplateArray } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './styles/editor.scss';
import {
	addClassToBody,
	useBlockPropsWithLocking,
} from '../cart-checkout/shared';
import { CheckoutBlockContext, CheckoutBlockControlsContext } from './context';
import type { Attributes } from './types';

// This is adds a class to body to signal if the selected block is locked
addClassToBody();

// Array of allowed block names.
const ALLOWED_BLOCKS: string[] = [
	'woocommerce/checkout-fields-block',
	'woocommerce/checkout-totals-block',
];

const BlockSettings = ( {
	attributes,
	setAttributes,
}: {
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => undefined;
} ): JSX.Element => {
	const { hasDarkControls } = attributes;
	const { currentPostId } = useEditorContext();

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
							'woo-gutenberg-products-block'
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
			<PanelBody title={ __( 'Style', 'woo-gutenberg-products-block' ) }>
				<ToggleControl
					label={ __(
						'Dark mode inputs',
						'woo-gutenberg-products-block'
					) }
					help={ __(
						'Inputs styled specifically for use on dark background colors.',
						'woo-gutenberg-products-block'
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

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => undefined;
} ): JSX.Element => {
	const {
		allowCreateAccount,
		showCompanyField,
		requireCompanyField,
		showApartmentField,
		showPhoneField,
		requirePhoneField,
		showOrderNotes,
		showPolicyLinks,
		showReturnToCart,
		showRateAfterTaxName,
		cartPageId,
	} = attributes;

	const defaultTemplate = [
		[ 'woocommerce/checkout-fields-block', {}, [] ],
		[ 'woocommerce/checkout-totals-block', {}, [] ],
	] as TemplateArray;

	const toggleAttribute = ( key: keyof Attributes ): void => {
		const newAttributes = {} as Partial< Attributes >;
		newAttributes[ key ] = ! ( attributes[ key ] as boolean );
		setAttributes( newAttributes );
	};

	const accountControls = (): JSX.Element => (
		<InspectorControls>
			<PanelBody
				title={ __(
					'Account options',
					'woo-gutenberg-products-block'
				) }
			>
				<ToggleControl
					label={ __(
						'Allow shoppers to sign up for a user account during checkout',
						'woo-gutenberg-products-block'
					) }
					checked={ allowCreateAccount }
					onChange={ () =>
						setAttributes( {
							allowCreateAccount: ! allowCreateAccount,
						} )
					}
				/>
			</PanelBody>
		</InspectorControls>
	);

	const addressFieldControls = (): JSX.Element => (
		<InspectorControls>
			<PanelBody
				title={ __( 'Address Fields', 'woo-gutenberg-products-block' ) }
			>
				<p className="wc-block-checkout__controls-text">
					{ __(
						'Show or hide fields in the checkout address forms.',
						'woo-gutenberg-products-block'
					) }
				</p>
				<ToggleControl
					label={ __( 'Company', 'woo-gutenberg-products-block' ) }
					checked={ showCompanyField }
					onChange={ () => toggleAttribute( 'showCompanyField' ) }
				/>
				{ showCompanyField && (
					<CheckboxControl
						label={ __(
							'Require company name?',
							'woo-gutenberg-products-block'
						) }
						checked={ requireCompanyField }
						onChange={ () =>
							toggleAttribute( 'requireCompanyField' )
						}
						className="components-base-control--nested"
					/>
				) }
				<ToggleControl
					label={ __(
						'Apartment, suite, etc.',
						'woo-gutenberg-products-block'
					) }
					checked={ showApartmentField }
					onChange={ () => toggleAttribute( 'showApartmentField' ) }
				/>
				<ToggleControl
					label={ __( 'Phone', 'woo-gutenberg-products-block' ) }
					checked={ showPhoneField }
					onChange={ () => toggleAttribute( 'showPhoneField' ) }
				/>
				{ showPhoneField && (
					<CheckboxControl
						label={ __(
							'Require phone number?',
							'woo-gutenberg-products-block'
						) }
						checked={ requirePhoneField }
						onChange={ () =>
							toggleAttribute( 'requirePhoneField' )
						}
						className="components-base-control--nested"
					/>
				) }
			</PanelBody>
		</InspectorControls>
	);
	const blockProps = useBlockPropsWithLocking();
	return (
		<div { ...blockProps }>
			<EditorProvider
				previewData={ { previewCart, previewSavedPaymentMethods } }
			>
				<BlockSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
				<CheckoutProvider>
					<SidebarLayout
						className={ classnames( 'wc-block-checkout', {
							'has-dark-controls': attributes.hasDarkControls,
						} ) }
					>
						<CheckoutBlockControlsContext.Provider
							value={ {
								addressFieldControls,
								accountControls,
							} }
						>
							<CheckoutBlockContext.Provider
								value={ {
									allowCreateAccount,
									showCompanyField,
									requireCompanyField,
									showApartmentField,
									showPhoneField,
									requirePhoneField,
									showOrderNotes,
									showPolicyLinks,
									showReturnToCart,
									cartPageId,
									showRateAfterTaxName,
								} }
							>
								<InnerBlocks
									allowedBlocks={ ALLOWED_BLOCKS }
									template={ defaultTemplate }
									templateLock="insert"
								/>
							</CheckoutBlockContext.Provider>
						</CheckoutBlockControlsContext.Provider>
					</SidebarLayout>
				</CheckoutProvider>
			</EditorProvider>
			<CartCheckoutCompatibilityNotice blockName="checkout" />
		</div>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div
			{ ...useBlockProps.save( {
				className: 'wc-block-checkout is-loading',
			} ) }
		>
			<InnerBlocks.Content />
		</div>
	);
};
