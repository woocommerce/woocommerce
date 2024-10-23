/**
 * External dependencies
 */
import clsx from 'clsx';
import { useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import PageSelector from '@woocommerce/editor-components/page-selector';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { CHECKOUT_PAGE_ID } from '@woocommerce/block-settings';
import { ReturnToCartButton } from '@woocommerce/base-components/cart-checkout';
import EditableButton from '@woocommerce/editor-components/editable-button';

/**
 * Internal dependencies
 */
import {
	defaultPlaceOrderButtonLabel,
	defaultReturnToCartButtonLabel,
} from './constants';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		showReturnToCart: boolean;
		cartPageId: number;
		placeOrderButtonLabel: string;
		returnToCartButtonLabel: string;
	};
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

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Navigation options', 'woocommerce' ) }>
					<ToggleControl
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
				<div className="wc-block-checkout__actions_row">
					{ showReturnToCart && (
						<ReturnToCartButton href="#cart-page-placeholder">
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
					<EditableButton
						className={ clsx(
							'wc-block-cart__submit-button',
							'wc-block-components-checkout-place-order-button',
							{
								'wc-block-components-checkout-place-order-button--full-width':
									! showReturnToCart,
							}
						) }
						value={ placeOrderButtonLabel }
						placeholder={ defaultPlaceOrderButtonLabel }
						onChange={ ( content ) => {
							setAttributes( {
								placeOrderButtonLabel: content,
							} );
						} }
					/>
				</div>
			</div>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
