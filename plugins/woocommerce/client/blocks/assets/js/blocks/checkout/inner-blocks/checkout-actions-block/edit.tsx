/**
 * External dependencies
 */
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Notice,
	PanelBody,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import PageSelector from '@woocommerce/editor-components/page-selector';
import { CHECKOUT_PAGE_ID } from '@woocommerce/block-settings';
import {
	PlaceOrderButton,
	ReturnToCartButton,
} from '@woocommerce/base-components/cart-checkout';
import clsx from 'clsx';
import { CheckboxControl } from '@woocommerce/blocks-components';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { BlockAttributes } from './block';
import { defaultReturnToCartButtonLabel } from './constants';
import { TermsNotices } from '../checkout-terms-block/edit';
import {
	termsConsentDefaultText,
	termsCheckboxDefaultText,
} from '../checkout-terms-block/constants';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: BlockAttributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ) => {
	const blockProps = useBlockProps();
	const {
		cartPageId = 0,
		showReturnToCart = false,
		placeOrderButtonLabel,
		returnToCartButtonLabel,
		showTerms = false,
		termsCheckbox = false,
		termsText = '',
	} = attributes;
	const { current: savedCartPageId } = useRef( cartPageId );
	const currentPostId = useSelect(
		( select ) => {
			if ( ! savedCartPageId ) {
				const store = select( 'core/editor' );
				return store.getCurrentPostId();
			}
			return savedCartPageId;
		},
		[ savedCartPageId ]
	);

	const showPrice = blockProps.className.includes( 'is-style-with-price' );

	const isTermsDisabledByExtension = ! getSetting(
		'checkoutShowTerms',
		true
	);

	// Mirrors the frontend: a required checkbox goes above the button, consent text below it.
	const terms =
		showTerms && ! isTermsDisabledByExtension ? (
			<div className="wc-block-checkout__terms">
				{ termsCheckbox && (
					<div className="wc-block-checkout__terms-checkbox-wrapper">
						<CheckboxControl
							id="terms-and-conditions"
							checked={ false }
							onChange={ () => undefined }
						/>
					</div>
				) }
				<RichText
					tagName="span"
					value={
						termsText ||
						( termsCheckbox
							? termsCheckboxDefaultText
							: termsConsentDefaultText )
					}
					onChange={ ( value ) =>
						setAttributes( { termsText: value } )
					}
				/>
			</div>
		) : null;

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Options', 'woocommerce' ) }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Show a "Return to Cart" link',
							'woocommerce'
						) }
						help={ __(
							'Recommended to enable only if there is no Cart link in the header.',
							'woocommerce'
						) }
						checked={ showReturnToCart }
						onChange={ () =>
							setAttributes( {
								showReturnToCart: ! showReturnToCart,
							} )
						}
					/>

					{ showPrice && (
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Price separator', 'woocommerce' ) }
							id="price-separator"
							value={ attributes.priceSeparator }
							onChange={ ( value ) => {
								setAttributes( {
									priceSeparator: value,
								} );
							} }
						/>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Terms and conditions', 'woocommerce' ) }
				>
					{ isTermsDisabledByExtension && (
						<Notice
							className="wc-block-checkout__terms_notice"
							status="warning"
							isDismissible={ false }
						>
							{ __(
								'Terms are hidden by an extension on your store.',
								'woocommerce'
							) }
						</Notice>
					) }
					{ showTerms && (
						<TermsNotices
							text={ termsText }
							checkbox={ termsCheckbox }
							setText={ ( value ) =>
								setAttributes( { termsText: value } )
							}
						/>
					) }
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Show terms and conditions',
							'woocommerce'
						) }
						checked={ showTerms }
						onChange={ () =>
							setAttributes( { showTerms: ! showTerms } )
						}
					/>
					{ showTerms && (
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Require checkbox', 'woocommerce' ) }
							checked={ termsCheckbox }
							onChange={ () =>
								setAttributes( {
									termsCheckbox: ! termsCheckbox,
								} )
							}
						/>
					) }
				</PanelBody>

				{ showReturnToCart &&
					! (
						currentPostId === CHECKOUT_PAGE_ID &&
						savedCartPageId === 0
					) && (
						<PageSelector
							pageId={ cartPageId }
							setPageId={ ( id: number ) =>
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
			</InspectorControls>
			<div className="wc-block-checkout__actions">
				{ termsCheckbox && terms }
				<div
					className={ clsx( 'wc-block-checkout__actions_row', {
						'wc-block-checkout__actions_row--justify-flex-end':
							! showReturnToCart,
						'wc-block-checkout__actions_row--with-return-to-cart':
							showReturnToCart,
					} ) }
				>
					{ showReturnToCart && (
						<ReturnToCartButton element="span">
							<RichText
								multiline={ false }
								allowedFormats={ [] }
								value={ returnToCartButtonLabel }
								placeholder={ defaultReturnToCartButtonLabel }
								onChange={ ( content ) => {
									setAttributes( {
										returnToCartButtonLabel: content,
									} );
								} }
							/>
						</ReturnToCartButton>
					) }
					<PlaceOrderButton
						label={
							<RichText
								multiline={ false }
								allowedFormats={ [] }
								value={ placeOrderButtonLabel }
								onChange={ ( content ) => {
									setAttributes( {
										placeOrderButtonLabel: content,
									} );
								} }
							/>
						}
						fullWidth
						showPrice={ showPrice }
						priceSeparator={ attributes.priceSeparator }
					/>
					{ ! termsCheckbox && terms }
				</div>
			</div>
		</div>
	);
};

export const Save = () => {
	return <div { ...useBlockProps.save() } />;
};
