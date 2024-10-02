/**
 * External dependencies
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { ProductShortDescriptionSkeleton } from '@woocommerce/base-components/skeleton/patterns/product-short-description';
import { BlockEditProps } from '@wordpress/blocks';
import { Disabled, Tooltip } from '@wordpress/components';
import { isSiteEditorPage } from '@woocommerce/utils';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { QuantitySelectorStyle, AddToCartFormSettings } from './settings';
import { UpgradeNotice } from './components/upgrade-notice';
import type { Attributes } from './';

const AddToCartFormEdit = ( props: BlockEditProps< Attributes > ) => {
	const { setAttributes } = props;

	const quantitySelectorStyleClass =
		props.attributes.quantitySelectorStyle === QuantitySelectorStyle.Input
			? 'wc-block-add-to-cart-form--input'
			: 'wc-block-add-to-cart-form--stepper';

	const blockProps = useBlockProps( {
		className: `wc-block-add-to-cart-form ${ quantitySelectorStyleClass }`,
	} );

	const isSiteEditor = isSiteEditorPage();

	const isBlockTheme = getSetting( 'isBlockTheme', false );
	const buttonBlockClass = ! isBlockTheme ? 'wp-block-button' : '';
	const buttonLinkClass = ! isBlockTheme
		? 'wp-block-button__link wc-block-components-button'
		: '';

	const {
		autoselect,
		autoselectOnPageLoad,
		unattachedAction,
	} = attributes;

	return (
		<>
			{ isBlockTheme && (
				<InspectorControls>
					<UpgradeNotice blockClientId={ props?.clientId } />
				</InspectorControls>
			) }
			<AddToCartFormSettings
				quantitySelectorStyle={ props.attributes.quantitySelectorStyle }
				setAttributes={ setAttributes }
			/>
			<div { ...blockProps }>
				<Tooltip
					text={ __(
						'Customer will see product add-to-cart options in this space, dependent on the product type.',
						'woocommerce'
					) }
					position="bottom right"
				>
					<div className="wc-block-editor-add-to-cart-form-container">
						<ProductShortDescriptionSkeleton isStatic={ true } />
						<Disabled>
							{ props.attributes.quantitySelectorStyle ===
								QuantitySelectorStyle.Input && (
								<>
									<div className="quantity">
										<input
											style={
												// In the post editor, the editor isn't in an iframe, so WordPress styles are applied. We need to remove them.
												! isSiteEditor
													? {
															backgroundColor:
																'#ffffff',
															lineHeight:
																'normal',
															minHeight: 'unset',
															boxSizing: 'unset',
															borderRadius:
																'unset',
													  }
													: {}
											}
											type="number"
											value="1"
											className="input-text qty text"
											readOnly
										/>
									</div>
									<div className={ buttonBlockClass }>
										<button
											className={ `single_add_to_cart_button alt wp-element-button ${ buttonLinkClass }` }
										>
											{ __(
												'Add to cart',
												'woocommerce'
											) }
										</button>
									</div>
								</>
							) }
							{ props.attributes.quantitySelectorStyle ===
								QuantitySelectorStyle.Stepper && (
								<>
									<div className="quantity wc-block-components-quantity-selector">
										<button className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--minus">
											−
										</button>
										<input
											style={
												// In the post editor, the editor isn't in an iframe, so WordPress styles are applied. We need to remove them.
												! isSiteEditor
													? {
															backgroundColor:
																'#ffffff',
															lineHeight:
																'normal',
															minHeight: 'unset',
															boxSizing: 'unset',
															borderRadius:
																'unset',
													  }
													: {}
											}
											type="number"
											value="1"
											className="input-text qty text"
											readOnly
										/>
										<button className="wc-block-components-quantity-selector__button wc-block-components-quantity-selector__button--plus">
											+
										</button>
									</div>
									<div className={ buttonBlockClass }>
										<button
											className={ `single_add_to_cart_button alt wp-element-button ${ buttonLinkClass }` }
										>
											{ __(
												'Add to cart',
												'woocommerce'
											) }
										</button>
									</div>
								</>
							) }
						</Disabled>
					</div>
				</Tooltip>
			</div>
			<InspectorControls key="inspector">
				<PanelBody title={ __( 'Autoselect', 'woocommerce' ) }>
					<ToggleControl
						label={ __( 'Auto-select attributes on user selection', 'woocommerce' ) }
						help={ __( 'This controls which other attributes will be auto-selected when an attribute is changed. Only attributes with a single compatible value will be auto-selected.', 'woocommerce' ) }
						checked={ autoselect }
						onChange={ () => setAttributes( { autoselect: !autoselect } ) }
						__nextHasNoMarginBottom
					/>
					<ToggleControl
						label={ __( 'Auto-select on page load', 'woocommerce' ) }
						help={ __( 'This controls whether or not attributes with only one possible option will be auto-selected upon loading the page.', 'woocommerce' ) }
						checked={ autoselectOnPageLoad }
						onChange={ () => setAttributes( { autoselectOnPageLoad: !autoselectOnPageLoad } ) }
						__nextHasNoMarginBottom
					/>
					<SelectControl
						label={ __( 'Values in conflict with current selection', 'woocommerce' ) }
						help={ __( 'This controls what to do with attribute values that conflict with the current selection.', 'woocommerce' ) }
						value={ unattachedAction }
						options={ [
							{ label: __( 'Hidden',                                                                  'woocommerce' ), value: 'hide' },
							{ label: __( 'Grayed-out and disabled',                                                 'woocommerce' ), value: 'disable' },
							{ label: __( 'Grayed-out but selectable (will clear all other attributes if selected)', 'woocommerce' ), value: 'gray' },
						] }
						onChange={ ( value ) => setAttributes( { unattachedAction: value } ) }
						__nextHasNoMarginBottom
					/>
				</PanelBody>
			</InspectorControls>
		</>
	);
};

export default AddToCartFormEdit;
