/**
 * External dependencies
 */
import {
	getConfig,
	getContext,
	store,
	type AsyncAction,
} from '@wordpress/interactivity';
import '@woocommerce/stores/woocommerce/shopper-lists';
import '@woocommerce/stores/woocommerce/cart';
import type {
	RawShopperListItem,
	ShopperListItemPrices,
	Store as ShopperListsStore,
} from '@woocommerce/stores/woocommerce/shopper-lists';
import type {
	Store as WooCommerce,
	WooCommerceConfig,
} from '@woocommerce/stores/woocommerce/cart';

/**
 * Internal dependencies
 */
import {
	formatPriceWithCurrency,
	normalizeCurrencyResponse,
} from '../../../../packages/prices/utils/currency';

const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

type ShopperCollectionConfig = {
	quantityLabelTemplate: string;
	removeLabelTemplate: string;
	moveToCartLabel: string;
	emptyMessage: string;
};

type BlockContext = {
	listSlug: string;
	listItem?: RawShopperListItem;
};

type BlockStore = {
	state: {
		currentItems: RawShopperListItem[];
		hasError: boolean;
		errorMessage: string;
		isEmpty: boolean;
		isMoveToCartHidden: boolean;
		isPriceHidden: boolean;
		currentItemThumbnail: string;
		currentItemAlt: string;
		currentItemDisplayName: string;
		currentItemQuantityLabel: string;
		currentItemRemoveLabel: string;
		currentItemFormattedPrice: string;
		currentItemVariationLabel: string;
	};
	actions: {
		onClickRemove: () => Generator< unknown, void >;
		onClickMoveToCart: () => Generator< unknown, void >;
	};
};

const { state: shopperListsState, actions: shopperListsActions } =
	store< ShopperListsStore >(
		'woocommerce/shopper-lists',
		{},
		{ lock: universalLock }
	);

const { state: cartState, actions: cartActions } = store< WooCommerce >(
	'woocommerce',
	{},
	{ lock: universalLock }
);

const { placeholderImgSrc, currency: defaultCurrency } = getConfig(
	'woocommerce'
) as WooCommerceConfig;

const decodeEntities = ( encoded: string ): string => {
	const txt = document.createElement( 'textarea' );
	txt.innerHTML = encoded;
	return txt.value;
};

const formatPriceFromSchema = (
	prices: ShopperListItemPrices | null | undefined
): string => {
	if ( ! prices || prices.price === '' || ! defaultCurrency ) {
		return '';
	}
	const itemCurrency = normalizeCurrencyResponse( prices, defaultCurrency );
	return formatPriceWithCurrency( prices.price, itemCurrency );
};

const formatVariationLabel = ( item: RawShopperListItem ): string => {
	if ( ! item.variation || item.variation.length === 0 ) {
		return '';
	}
	return item.variation
		.map(
			( v ) =>
				`${ decodeEntities( v.attribute ) }: ${ decodeEntities(
					v.value
				) }`
		)
		.join( ', ' );
};

const getList = ( slug: string ) => shopperListsState.lists[ slug ] ?? null;

store< BlockStore >(
	'woocommerce/shopper-collection',
	{
		state: {
			get currentItems(): RawShopperListItem[] {
				const { listSlug } = getContext< BlockContext >();
				return getList( listSlug )?.items ?? [];
			},

			get hasError(): boolean {
				const { listSlug } = getContext< BlockContext >();
				return !! getList( listSlug )?.error;
			},

			get errorMessage(): string {
				const { listSlug } = getContext< BlockContext >();
				return getList( listSlug )?.error ?? '';
			},

			get isEmpty(): boolean {
				const { listSlug } = getContext< BlockContext >();
				const list = getList( listSlug );
				if ( ! list ) {
					return true;
				}
				return ! list.isLoading && list.items.length === 0;
			},

			get hasVariation(): boolean {
				const { listItem } = getContext< BlockContext >();
				return !! listItem && listItem.variation.length > 0;
			},

			get isPriceHidden(): boolean {
				const { listItem } = getContext< BlockContext >();
				return ! listItem?.product_exists || ! listItem.prices;
			},

			get isMoveToCartHidden(): boolean {
				const { listItem } = getContext< BlockContext >();
				if ( ! listItem ) {
					return true;
				}
				// Tombstones never have a buyable product.
				return ! listItem.product_exists;
			},

			get currentItemThumbnail(): string {
				const { listItem } = getContext< BlockContext >();
				return (
					listItem?.images?.[ 0 ]?.thumbnail ||
					placeholderImgSrc ||
					''
				);
			},

			get currentItemAlt(): string {
				const { listItem } = getContext< BlockContext >();
				return listItem ? decodeEntities( listItem.name ) : '';
			},

			// `data-wp-text` writes its argument as text-content without
			// running entity decoding, so a name returned by the schema as
			// `Tom &amp; Jerry` would render literally that way. Bind
			// templates and SSR text spans to this getter instead of the
			// raw context field so what the browser shows matches what
			// PHP wrote on first paint.
			get currentItemDisplayName(): string {
				const { listItem } = getContext< BlockContext >();
				return listItem ? decodeEntities( listItem.name ) : '';
			},

			get currentItemQuantityLabel(): string {
				const { listItem } = getContext< BlockContext >();
				if ( ! listItem ) {
					return '';
				}
				const { quantityLabelTemplate } = getConfig(
					'woocommerce/shopper-collection'
				) as ShopperCollectionConfig;
				return quantityLabelTemplate.replace(
					'%d',
					String( listItem.quantity )
				);
			},

			get currentItemRemoveLabel(): string {
				const { listItem } = getContext< BlockContext >();
				if ( ! listItem ) {
					return '';
				}
				const { removeLabelTemplate } = getConfig(
					'woocommerce/shopper-collection'
				) as ShopperCollectionConfig;
				return removeLabelTemplate.replace(
					'%s',
					decodeEntities( listItem.name )
				);
			},

			get currentItemFormattedPrice(): string {
				const { listItem } = getContext< BlockContext >();
				return formatPriceFromSchema( listItem?.prices );
			},

			get currentItemVariationLabel(): string {
				const { listItem } = getContext< BlockContext >();
				return listItem ? formatVariationLabel( listItem ) : '';
			},
		},

		actions: {
			*onClickRemove(): AsyncAction< void > {
				const { listSlug, listItem } = getContext< BlockContext >();
				if ( ! listItem ) {
					return;
				}
				yield shopperListsActions.removeItem( listSlug, listItem.key );
			},

			*onClickMoveToCart(): AsyncAction< void > {
				const { listSlug, listItem } = getContext< BlockContext >();
				if ( ! listItem || ! listItem.product_exists ) {
					return;
				}

				// Map the schema's `variation` shape to the cart's
				// SelectedAttributes shape. The schema returns the
				// slug-form attribute under `raw_attribute` (e.g.
				// `attribute_pa_color`) plus a display label under
				// `attribute` (e.g. "Color"); the cart matches by the
				// slug-form, so override `attribute` with `raw_attribute`.
				// Same swap mini-cart's `changeQuantity` does. Empty for
				// simple products.
				const variation = listItem.variation.map(
					( { raw_attribute: rawAttribute, value, attribute } ) => ( {
						attribute: rawAttribute || attribute,
						value,
					} )
				);
				const isVariation = listItem.variation_id > 0;

				// `cartActions.addCartItem` catches its own errors and
				// surfaces them as store notices, so the yield resolves
				// the same way on success and failure. Snapshot the
				// matching line's quantity, run the add, then only remove
				// from the saved list if it actually grew.
				const lookup = {
					id: listItem.id,
					...( isVariation && { variation } ),
				};
				const beforeItem = cartState.findItemInCart( lookup );
				const beforeQuantity = beforeItem?.quantity ?? 0;

				yield cartActions.addCartItem( {
					id: listItem.id,
					quantityToAdd: listItem.quantity,
					type: isVariation ? 'variation' : 'simple',
					...( isVariation && { variation } ),
				} );

				const afterItem = cartState.findItemInCart( lookup );
				const afterQuantity = afterItem?.quantity ?? 0;

				if ( afterQuantity <= beforeQuantity ) {
					return;
				}

				yield shopperListsActions.removeItem( listSlug, listItem.key );
			},
		},
	},
	{ lock: universalLock }
);
