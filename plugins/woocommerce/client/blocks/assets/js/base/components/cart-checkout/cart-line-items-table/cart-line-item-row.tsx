/**
 * External dependencies
 */
import clsx from 'clsx';
import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { speak } from '@wordpress/a11y';
import QuantitySelector from '@woocommerce/base-components/quantity-selector';
import ProductPrice from '@woocommerce/base-components/product-price';
import ProductName from '@woocommerce/base-components/product-name';
import {
	useStoreCartItemQuantity,
	useStoreEvents,
	useStoreCart,
	useSaveForLater,
} from '@woocommerce/base-context/hooks';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	applyCheckoutFilter,
	productPriceValidation,
} from '@woocommerce/blocks-checkout';
import { forwardRef, useMemo } from '@wordpress/element';
import type { CartItem } from '@woocommerce/types';
import { isBoolean, Currency } from '@woocommerce/types';
import { getSetting, getSettingWithCoercion } from '@woocommerce/settings';
import { Icon, trash } from '@wordpress/icons';
import { calculateSaleAmount } from '@woocommerce/base-utils';
import { dinero, transformScale, toSnapshot, type Dinero } from 'dinero.js';
import { USD } from 'dinero.js/currencies'; // USD is used as a placeholder currency for arithmetic; actual formatting is handled elsewhere.

/**
 * Internal dependencies
 */
import ProductBackorderBadge from '../product-backorder-badge';
import ProductImage from '../product-image';
import ProductMetadata from '../product-metadata';
import ProductSaleBadge from '../product-sale-badge';

/**
 * Convert a Dinero object with precision to store currency minor unit.
 *
 * @param {Dinero} priceObject Price object to convert.
 * @param {Object} currency    Currency data.
 * @return {number} Amount with new minor unit precision.
 */
const getAmountFromRawPrice = (
	priceObject: Dinero< number >,
	currency: Currency
) => {
	return toSnapshot( transformScale( priceObject, currency.minorUnit ) )
		.amount;
};

interface CartLineItemRowProps {
	lineItem: CartItem | Record< string, never >;
	onRemove?: () => void;
	tabIndex?: number;
}

/**
 * Cart line item table row component.
 */
const CartLineItemRow: React.ForwardRefExoticComponent<
	CartLineItemRowProps & React.RefAttributes< HTMLTableRowElement >
> = forwardRef< HTMLTableRowElement, CartLineItemRowProps >(
	(
		{ lineItem, onRemove = () => void null, tabIndex },
		ref
	): JSX.Element => {
		const {
			name: initialName = '',
			catalog_visibility: catalogVisibility = 'visible',
			short_description: shortDescription = '',
			description: fullDescription = '',
			show_backorder_badge: showBackorderBadge = false,
			quantity_limits: quantityLimits = {
				minimum: 1,
				maximum: 99,
				multiple_of: 1,
				editable: true,
			},
			sold_individually: soldIndividually = false,
			permalink = '',
			images = [],
			variation = [],
			item_data: itemData = [],
			prices = {
				currency_code: 'USD',
				currency_minor_unit: 2,
				currency_symbol: '$',
				currency_prefix: '$',
				currency_suffix: '',
				currency_decimal_separator: '.',
				currency_thousand_separator: ',',
				price: '0',
				regular_price: '0',
				sale_price: '0',
				price_range: null,
				raw_prices: {
					precision: 6,
					price: '0',
					regular_price: '0',
					sale_price: '0',
				},
			},
			totals = {
				currency_code: 'USD',
				currency_minor_unit: 2,
				currency_symbol: '$',
				currency_prefix: '$',
				currency_suffix: '',
				currency_decimal_separator: '.',
				currency_thousand_separator: ',',
				line_subtotal: '0',
				line_subtotal_tax: '0',
			},
			extensions,
		} = lineItem;

		const { quantity, setItemQuantity, removeItem, isPendingDelete } =
			useStoreCartItemQuantity( lineItem );
		const { saveForLater, isSaving: isSavingForLater } = useSaveForLater();
		const { dispatchStoreEvent } = useStoreEvents();
		const isUserLoggedIn = !! getSetting< number >( 'currentUserId', 0 );
		const isSaveForLaterFeatureEnabled = getSettingWithCoercion(
			'experimentalCartSaveForLater',
			false,
			isBoolean
		);
		const cartPageHasSavedForLater = getSettingWithCoercion(
			'cartPageHasSavedForLater',
			false,
			isBoolean
		);
		// Three signals, each catching a distinct failure mode.
		// Disabling the `cart_save_for_later` feature unregisters the
		// saved-for-later block but leaves any prior insertion in the
		// cart page's post content (the editor renders it as an
		// "unsupported block" notice) — so presence alone could render
		// this link with no working destination. Inversely, the feature
		// can be enabled on cart pages that never inserted the block.
		// And the REST endpoints behind the click are auth-only.
		const showSaveForLater =
			isUserLoggedIn &&
			isSaveForLaterFeatureEnabled &&
			cartPageHasSavedForLater;

		// Prepare props to pass to the applyCheckoutFilter filter.
		// We need to pluck out receiveCart.
		// eslint-disable-next-line no-unused-vars
		const { receiveCart, ...cart } = useStoreCart();
		const arg = useMemo(
			() => ( {
				context: 'cart',
				cartItem: lineItem,
				cart,
			} ),
			[ lineItem, cart ]
		);
		const priceCurrency = getCurrencyFromPriceResponse( prices );
		const name = applyCheckoutFilter( {
			filterName: 'itemName',
			defaultValue: initialName,
			extensions,
			arg,
		} );
		// `name` is a raw HTML string; decode entities for screen-reader text (aria-label, speak).
		const decodedName = decodeEntities( name );

		const regularAmountSingle = dinero( {
			amount: parseInt( prices.raw_prices.regular_price, 10 ),
			currency: USD,
			scale: prices.raw_prices.precision,
		} );
		const purchaseAmountSingle = dinero( {
			amount: parseInt( prices.raw_prices.price, 10 ),
			currency: USD,
			scale: prices.raw_prices.precision,
		} );
		const saleAmountSingle = calculateSaleAmount(
			prices,
			priceCurrency.minorUnit
		);
		const totalsCurrency = getCurrencyFromPriceResponse( totals );
		let lineSubtotal = parseInt( totals.line_subtotal, 10 );
		if ( getSetting( 'displayCartPricesIncludingTax', false ) ) {
			lineSubtotal += parseInt( totals.line_subtotal_tax, 10 );
		}
		const subtotalPrice = dinero( {
			amount: lineSubtotal,
			currency: USD,
			scale: totalsCurrency.minorUnit,
		} );

		const firstImage = images.length ? images[ 0 ] : {};
		const isProductHiddenFromCatalog =
			catalogVisibility === 'hidden' || catalogVisibility === 'search';

		const cartItemClassNameFilter = applyCheckoutFilter( {
			filterName: 'cartItemClass',
			defaultValue: '',
			extensions,
			arg,
		} );

		// Allow extensions to filter how the price is displayed. Ie: prepending or appending some values.
		const productPriceFormat = applyCheckoutFilter( {
			filterName: 'cartItemPrice',
			defaultValue: '<price/>',
			extensions,
			arg,
			validation: productPriceValidation,
		} );

		const subtotalPriceFormat = applyCheckoutFilter( {
			filterName: 'subtotalPriceFormat',
			defaultValue: '<price/>',
			extensions,
			arg,
			validation: productPriceValidation,
		} );

		const saleBadgePriceFormat = applyCheckoutFilter( {
			filterName: 'saleBadgePriceFormat',
			defaultValue: '<price/>',
			extensions,
			arg,
			validation: productPriceValidation,
		} );

		const showRemoveItemLink = applyCheckoutFilter( {
			filterName: 'showRemoveItemLink',
			defaultValue: true,
			extensions,
			arg,
		} );

		return (
			<tr
				// Restores the row role that `display: grid` strips in the responsive layout.
				role="row"
				data-cart-item-key={ lineItem.key }
				className={ clsx(
					'wc-block-cart-items__row',
					cartItemClassNameFilter,
					{
						'is-disabled': isPendingDelete,
					}
				) }
				ref={ ref }
				tabIndex={ tabIndex }
			>
				{ /* Decorative image, hidden from screen readers so the row isn't announced as an empty "Product" cell. */ }
				<td className="wc-block-cart-item__image" aria-hidden="true">
					{ isProductHiddenFromCatalog ? (
						<ProductImage
							image={ firstImage }
							fallbackAlt={ name }
							width={ 80 }
							height={ 80 }
						/>
					) : (
						<a href={ permalink } tabIndex={ -1 }>
							<ProductImage
								image={ firstImage }
								fallbackAlt={ name }
								width={ 80 }
								height={ 80 }
							/>
						</a>
					) }
				</td>
				<td
					role="rowheader"
					// Name the rowheader after the product only, not the whole cell's contents.
					aria-label={ decodedName }
					className="wc-block-cart-item__product"
				>
					<div className="wc-block-cart-item__wrap">
						<ProductName
							disabled={
								isPendingDelete || isProductHiddenFromCatalog
							}
							name={ name }
							permalink={ permalink }
						/>
						{ showBackorderBadge && <ProductBackorderBadge /> }

						<div className="wc-block-cart-item__prices">
							<ProductPrice
								currency={ priceCurrency }
								regularPrice={ getAmountFromRawPrice(
									regularAmountSingle,
									priceCurrency
								) }
								price={ getAmountFromRawPrice(
									purchaseAmountSingle,
									priceCurrency
								) }
								format={ subtotalPriceFormat }
							/>
						</div>

						<ProductMetadata
							shortDescription={ shortDescription }
							fullDescription={ fullDescription }
							itemData={ itemData }
							variation={ variation }
						/>

						<div className="wc-block-cart-item__quantity">
							{ ! soldIndividually && (
								<QuantitySelector
									disabled={ isPendingDelete }
									editable={ quantityLimits.editable }
									quantity={ quantity }
									minimum={ quantityLimits.minimum }
									maximum={ quantityLimits.maximum }
									step={ quantityLimits.multiple_of }
									onChange={ ( newQuantity ) => {
										setItemQuantity( newQuantity );
										dispatchStoreEvent(
											'cart-set-item-quantity',
											{
												product: lineItem,
												quantity: newQuantity,
											}
										);
									} }
									itemName={ decodedName }
								/>
							) }
							{ showRemoveItemLink && (
								<button
									className="wc-block-cart-item__remove-link"
									aria-label={ sprintf(
										/* translators: %s refers to the item's name in the cart. */
										__(
											'Remove %s from cart',
											'woocommerce'
										),
										decodedName
									) }
									onClick={ () => {
										onRemove();
										void removeItem();
										dispatchStoreEvent(
											'cart-remove-item',
											{
												product: lineItem,
												quantity,
											}
										);
										speak(
											sprintf(
												/* translators: %s refers to the item name in the cart. */
												__(
													'%s has been removed from your cart.',
													'woocommerce'
												),
												decodedName
											)
										);
									} }
									disabled={ isPendingDelete }
								>
									<Icon icon={ trash } size={ 24 } />
								</button>
							) }
						</div>
						{ showSaveForLater && (
							<div className="wc-block-cart-item__save-for-later">
								<button
									type="button"
									className="wc-block-cart-item__save-for-later-link"
									onClick={ async () => {
										const saved = await saveForLater(
											lineItem.key
										);
										if ( ! saved ) {
											return;
										}
										// removeItem surfaces its own errors
										// via processErrorResponse; we still
										// fire the analytics event and a11y
										// announcement to mirror the regular
										// remove flow.
										await removeItem();
										// TODO: consider a dedicated
										// 'cart-save-for-later' store event so
										// analytics can distinguish a save
										// from a plain remove.
										dispatchStoreEvent(
											'cart-remove-item',
											{
												product: lineItem,
												quantity,
											}
										);
										speak(
											sprintf(
												/* translators: %s refers to the item name. */
												__(
													'%s has been saved for later and removed from your cart.',
													'woocommerce'
												),
												decodedName
											)
										);
									} }
									disabled={
										isPendingDelete || isSavingForLater
									}
								>
									{ isSavingForLater
										? __( 'Saving…', 'woocommerce' )
										: __(
												'Save for later',
												'woocommerce'
										  ) }
								</button>
							</div>
						) }
					</div>
				</td>
				<td className="wc-block-cart-item__total">
					<div className="wc-block-cart-item__total-price-and-sale-badge-wrapper">
						<ProductPrice
							currency={ totalsCurrency }
							format={ productPriceFormat }
							price={ toSnapshot( subtotalPrice ).amount }
						/>

						<ProductSaleBadge
							currency={ priceCurrency }
							saleAmount={ saleAmountSingle * quantity }
							format={ saleBadgePriceFormat }
						/>
					</div>
				</td>
			</tr>
		);
	}
);
export default CartLineItemRow;
