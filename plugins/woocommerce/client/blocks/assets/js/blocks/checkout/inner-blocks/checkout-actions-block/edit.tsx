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
import PageSelector from '@woocommerce/editor-components/page-selector';
import { PanelBody, ToggleControl, TextControl } from '@wordpress/components';
import { CHECKOUT_PAGE_ID } from '@woocommerce/block-settings';
import {
	PlaceOrderButton,
	ReturnToCartButton,
} from '@woocommerce/base-components/cart-checkout';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { BlockAttributes } from './block';
import { defaultReturnToCartButtonLabel } from './constants';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: BlockAttributes;
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const blockProps = useBlockProps();
	const {
		cartPageId = 0,
		showReturnToCart = false,
		placeOrderButtonLabel,
		returnToCartButtonLabel,
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
				<div
					className={ clsx( 'wc-block-checkout__actions_row', {
						'wc-block-checkout__actions_row--justify-flex-end':
							! showReturnToCart,
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
						fullWidth={ ! showReturnToCart }
						showPrice={ showPrice }
						priceSeparator={ attributes.priceSeparator }
					/>
				</div>
			</div>
		</div>
	);
};

export const Save = () => {
	return <div { ...useBlockProps.save() } />;
};
