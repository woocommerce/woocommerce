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
import { getSetting } from '@woocommerce/settings';
import { ReturnToCartButton } from '@woocommerce/base-components/cart-checkout';
import EditableButton from '@woocommerce/editor-components/editable-button';
import Noninteractive from '@woocommerce/base-components/noninteractive';
import { useStoreCart } from '@woocommerce/base-context';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { FormattedMonetaryAmount } from '@woocommerce/blocks-components';

/**
 * Internal dependencies
 */
import { defaultPlaceOrderButtonLabel } from './constants';
import './editor.scss';

export const Edit = ( {
	attributes,
	setAttributes,
}: {
	attributes: {
		showReturnToCart: boolean;
		showPrice: boolean;
		cartPageId: number;
		placeOrderButtonLabel: string;
		separatorText: string;
	};
	setAttributes: ( attributes: Record< string, unknown > ) => void;
} ): JSX.Element => {
	const blockProps = useBlockProps();
	const {
		cartPageId = 0,
		showReturnToCart = false,
		placeOrderButtonLabel,
		showPrice = false,
		separatorText = '·',
	} = attributes;
	const { cartTotals } = useStoreCart();
	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

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
					<ToggleControl
						label={ __(
							'Show price in the button',
							'woocommerce'
						) }
						checked={ showPrice }
						onChange={ () =>
							setAttributes( {
								showPrice: ! showPrice,
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
					<Noninteractive>
						{ showReturnToCart && (
							<ReturnToCartButton
								link={ getSetting(
									'page-' + cartPageId,
									false
								) }
							/>
						) }
					</Noninteractive>
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
					>
						{ showPrice && (
							<>
								<RichText
									multiline={ false }
									allowedFormats={ [] }
									value={ separatorText }
									placeholder="·"
									className="wc-block-components-checkout-place-order-button__separator"
									onChange={ ( newSeparator ) => {
										setAttributes( {
											separatorText: newSeparator,
										} );
									} }
								/>
								<div className="wc-block-components-checkout-place-order-button__price">
									<FormattedMonetaryAmount
										value={ cartTotals.total_price }
										currency={ totalsCurrency }
									/>
								</div>
							</>
						) }
					</EditableButton>
				</div>
			</div>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return <div { ...useBlockProps.save() } />;
};
