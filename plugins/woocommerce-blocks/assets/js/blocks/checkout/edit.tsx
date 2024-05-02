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
import { CheckoutProvider, EditorProvider } from '@woocommerce/base-context';
import {
	previewCart,
	previewSavedPaymentMethods,
} from '@woocommerce/resource-previews';
import { PanelBody, ToggleControl, RadioControl } from '@wordpress/components';
import { SlotFillProvider } from '@woocommerce/blocks-checkout';
import type { TemplateArray } from '@wordpress/blocks';
import { useEffect, useRef } from '@wordpress/element';
import { getQueryArg } from '@wordpress/url';
import { dispatch, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import './inner-blocks';
import './styles/editor.scss';
import {
	addClassToBody,
	BlockSettings,
	useBlockPropsWithLocking,
} from '../cart-checkout-shared';
import '../cart-checkout-shared/sidebar-notices';
import { CheckoutBlockContext, CheckoutBlockControlsContext } from './context';
import type { Attributes } from './types';

// This is adds a class to body to signal if the selected block is locked
addClassToBody();

// Array of allowed block names.
const ALLOWED_BLOCKS: string[] = [
	'woocommerce/checkout-fields-block',
	'woocommerce/checkout-totals-block',
];

export const Edit = ( {
	clientId,
	attributes,
	setAttributes,
}: {
	clientId: string;
	attributes: Attributes;
	setAttributes: ( attributes: Record< string, unknown > ) => undefined;
} ): JSX.Element => {
	const {
		showCompanyField,
		requireCompanyField,
		showApartmentField,
		requireApartmentField,
		showPhoneField,
		requirePhoneField,
		showOrderNotes,
		showPolicyLinks,
		showReturnToCart,
		showRateAfterTaxName,
		cartPageId,
		isPreview = false,
	} = attributes;

	// This focuses on the block when a certain query param is found. This is used on the link from the task list.
	const focus = useRef( getQueryArg( window.location.href, 'focus' ) );

	useEffect( () => {
		if (
			focus.current === 'checkout' &&
			! select( 'core/block-editor' ).hasSelectedBlock()
		) {
			dispatch( 'core/block-editor' ).selectBlock( clientId );
			dispatch( 'core/interface' ).enableComplementaryArea(
				'core/edit-site',
				'edit-site/block-inspector'
			);
		}
	}, [ clientId ] );

	const defaultTemplate = [
		[ 'woocommerce/checkout-fields-block', {}, [] ],
		[ 'woocommerce/checkout-totals-block', {}, [] ],
	] as TemplateArray;

	const toggleAttribute = ( key: keyof Attributes ): void => {
		const newAttributes = {} as Partial< Attributes >;
		newAttributes[ key ] = ! ( attributes[ key ] as boolean );
		setAttributes( newAttributes );
	};

	const addressFieldControls = (): JSX.Element => (
		<InspectorControls>
			<PanelBody title={ __( 'Address Fields', 'woocommerce' ) }>
				<p className="wc-block-checkout__controls-text">
					{ __(
						'Show or hide fields in the checkout address forms.',
						'woocommerce'
					) }
				</p>
				<ToggleControl
					label={ __( 'Company', 'woocommerce' ) }
					checked={ showCompanyField }
					onChange={ () => toggleAttribute( 'showCompanyField' ) }
				/>
				{ showCompanyField && (
					<RadioControl
						selected={ requireCompanyField }
						options={ [
							{
								label: __(
									'Optional (Recommended)',
									'woocommerce'
								),
								value: false,
							},
							{
								label: __( 'Required', 'woocommerce' ),
								value: true,
							},
						] }
						onChange={ () =>
							toggleAttribute( 'requireCompanyField' )
						}
						className="components-base-control--nested"
					/>
				) }
				<ToggleControl
					label={ __( 'Address line 2', 'woocommerce' ) }
					checked={ showApartmentField }
					onChange={ () => toggleAttribute( 'showApartmentField' ) }
				/>
				{ showApartmentField && (
					<RadioControl
						selected={ requireApartmentField }
						options={ [
							{
								label: __(
									'Optional (Recommended)',
									'woocommerce'
								),
								value: false,
							},
							{
								label: __( 'Required', 'woocommerce' ),
								value: true,
							},
						] }
						onChange={ () =>
							toggleAttribute( 'requireApartmentField' )
						}
						className="components-base-control--nested"
					/>
				) }
				<ToggleControl
					label={ __( 'Phone', 'woocommerce' ) }
					checked={ showPhoneField }
					onChange={ () => toggleAttribute( 'showPhoneField' ) }
				/>
				{ showPhoneField && (
					<RadioControl
						selected={ requirePhoneField }
						options={ [
							{
								label: __(
									'Optional (Recommended)',
									'woocommerce'
								),
								value: false,
							},
							{
								label: __( 'Required', 'woocommerce' ),
								value: true,
							},
						] }
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
			<InspectorControls>
				<BlockSettings
					attributes={ attributes }
					setAttributes={ setAttributes }
				/>
			</InspectorControls>
			<EditorProvider
				isPreview={ isPreview }
				previewData={ { previewCart, previewSavedPaymentMethods } }
			>
				<SlotFillProvider>
					<CheckoutProvider>
						<SidebarLayout
							className={ classnames( 'wc-block-checkout', {
								'has-dark-controls': attributes.hasDarkControls,
							} ) }
						>
							<CheckoutBlockControlsContext.Provider
								value={ { addressFieldControls } }
							>
								<CheckoutBlockContext.Provider
									value={ {
										showApartmentField,
										showCompanyField,
										showPhoneField,
										requireApartmentField,
										requireCompanyField,
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
				</SlotFillProvider>
			</EditorProvider>
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
