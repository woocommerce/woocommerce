/**
 * External dependencies
 */
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { dispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { SITE_CURRENCY } from '@woocommerce/settings';
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Placeholder product IDs use large negative numbers to avoid
 * collisions with real product IDs.
 */
const PLACEHOLDER_ID_BASE = -999000;
const PLACEHOLDER_PRICE = '9.99';

/**
 * Returns the placeholder price converted to minor currency units.
 */
const getPlaceholderPriceMinorUnits = () =>
	String(
		Math.round(
			parseFloat( PLACEHOLDER_PRICE ) *
				Math.pow( 10, SITE_CURRENCY.minorUnit )
		)
	);

/**
 * Creates a placeholder product in Store API format (ProductResponseItem).
 * Used by ProductDataContextProvider so child blocks (price, image, button)
 * can render previews without fetching from the Store API.
 */
const createPlaceholderResponseItem = ( id: number ): ProductResponseItem => {
	const placeholderName = __( 'Product name', 'woocommerce' );
	const priceInMinorUnits = getPlaceholderPriceMinorUnits();

	return {
		id,
		name: placeholderName,
		parent: 0,
		type: 'simple',
		variation: '',
		permalink: '',
		sku: '',
		slug: `placeholder-product-${ id }`,
		short_description: '',
		description: '',
		on_sale: false,
		prices: {
			currency_code: SITE_CURRENCY.code,
			currency_symbol: SITE_CURRENCY.symbol,
			currency_minor_unit: SITE_CURRENCY.minorUnit,
			currency_decimal_separator: SITE_CURRENCY.decimalSeparator,
			currency_thousand_separator: SITE_CURRENCY.thousandSeparator,
			currency_prefix: SITE_CURRENCY.prefix,
			currency_suffix: SITE_CURRENCY.suffix,
			price: priceInMinorUnits,
			regular_price: priceInMinorUnits,
			sale_price: priceInMinorUnits,
			price_range: null,
		},
		price_html: '',
		average_rating: '0',
		review_count: 0,
		images: [],
		categories: [],
		tags: [],
		attributes: [],
		variations: [],
		has_options: false,
		is_purchasable: false,
		is_in_stock: true,
		is_on_backorder: false,
		low_stock_remaining: null,
		stock_availability: { text: '', class: '' },
		sold_individually: false,
		weight: '',
		dimensions: { length: '', width: '', height: '' },
		formatted_weight: '',
		formatted_dimensions: '',
		price: PLACEHOLDER_PRICE,
		regular_price: PLACEHOLDER_PRICE,
		sale_price: '',
		add_to_cart: {
			text: __( 'Add to cart', 'woocommerce' ),
			description: __( 'Add to cart', 'woocommerce' ),
			url: '',
			minimum: 1,
			maximum: 99,
			multiple_of: 1,
			single_text: __( 'Add to cart', 'woocommerce' ),
		},
		grouped_products: [],
	};
};

/**
 * Creates placeholder product entities and injects them into the
 * WordPress core data stores so that child blocks (product-image,
 * product-price, product-button, post-title) render meaningful
 * previews instead of hardcoded HTML.
 *
 * Two entity stores are populated:
 * - ('postType', 'product') for core post-title block (uses useEntityProp)
 * - ('root', 'product') for WooCommerce child blocks (uses useProduct hook)
 */
export const usePlaceholderProducts = ( {
	isPreviewWithNoProducts,
	count,
}: {
	isPreviewWithNoProducts: boolean;
	count: number;
} ) => {
	const [ entitiesReady, setEntitiesReady ] = useState( false );

	const placeholderIds = useMemo( () => {
		if ( ! isPreviewWithNoProducts ) {
			return [];
		}
		const safeCount = Math.max( 1, Math.min( count, 10 ) );
		return Array.from(
			{ length: safeCount },
			( _, i ) => PLACEHOLDER_ID_BASE - i
		);
	}, [ isPreviewWithNoProducts, count ] );

	useEffect( () => {
		if ( ! isPreviewWithNoProducts || placeholderIds.length === 0 ) {
			setEntitiesReady( false );
			return;
		}

		const placeholderName = __( 'Product name', 'woocommerce' );
		const storeActions = dispatch( coreStore );

		// WP REST API format — used by core post-title block via useEntityProp.
		const wpEntities = placeholderIds.map( ( id ) => ( {
			id,
			type: 'product',
			status: 'publish',
			title: {
				rendered: placeholderName,
				raw: placeholderName,
			},
		} ) );

		const priceInMinorUnits = getPlaceholderPriceMinorUnits();

		// WC REST API format — used by WooCommerce child blocks via useProduct hook.
		// Includes both REST API fields (price, regular_price) and Store API fields
		// (prices object) to support both experimental and non-experimental code paths.
		const wcEntities = placeholderIds.map( ( id ) => ( {
			id,
			name: placeholderName,
			slug: `placeholder-product-${ id }`,
			type: 'simple' as const,
			status: 'publish' as const,
			permalink: '',
			price: PLACEHOLDER_PRICE,
			regular_price: PLACEHOLDER_PRICE,
			sale_price: '',
			average_rating: '0',
			rating_count: 0,
			stock_status: 'instock' as const,
			images: [],
			featured: false,
			catalog_visibility: 'visible' as const,
			description: '',
			short_description: '',
			sku: '',
			prices: {
				currency_code: SITE_CURRENCY.code,
				currency_symbol: SITE_CURRENCY.symbol,
				currency_minor_unit: SITE_CURRENCY.minorUnit,
				currency_prefix: SITE_CURRENCY.prefix,
				currency_suffix: SITE_CURRENCY.suffix,
				currency_decimal_separator: SITE_CURRENCY.decimalSeparator,
				currency_thousand_separator: SITE_CURRENCY.thousandSeparator,
				price: priceInMinorUnits,
				regular_price: priceInMinorUnits,
				sale_price: priceInMinorUnits,
				price_range: null,
			},
		} ) );

		// Inject into both entity stores.
		// Args: kind, name, records, query, invalidateCache, edits, meta.
		storeActions.receiveEntityRecords(
			'postType',
			'product',
			wpEntities,
			null,
			false,
			null,
			null
		);
		storeActions.receiveEntityRecords(
			'root',
			'product',
			wcEntities,
			null,
			false,
			null,
			null
		);

		// Mark resolutions as finished to prevent API fetch attempts
		// for these placeholder IDs. Both getEntityRecord (the actual
		// resolver) and getEditedEntityRecord (checked by useProduct
		// hook's hasFinishedResolution) need to be finished.
		for ( const id of placeholderIds ) {
			storeActions.finishResolution( 'getEntityRecord', [
				'root',
				'product',
				id,
			] );
			storeActions.finishResolution( 'getEntityRecord', [
				'postType',
				'product',
				id,
			] );
			storeActions.finishResolution( 'getEditedEntityRecord', [
				'root',
				'product',
				id,
			] );
			storeActions.finishResolution( 'getEditedEntityRecord', [
				'postType',
				'product',
				id,
			] );
		}

		setEntitiesReady( true );
	}, [ isPreviewWithNoProducts, placeholderIds ] );

	const blockContexts = useMemo( () => {
		if ( ! entitiesReady ) {
			return null;
		}
		return placeholderIds.map( ( id ) => ( {
			postType: 'product',
			postId: id,
		} ) );
	}, [ entitiesReady, placeholderIds ] );

	// Store API format products for ProductDataContextProvider.
	// This ensures child blocks (price, image, button) that use
	// withProductDataContext HOC can access product data without
	// fetching from the Store API.
	const placeholderProductMap = useMemo( () => {
		if ( ! entitiesReady ) {
			return new Map< number, ProductResponseItem >();
		}
		return new Map(
			placeholderIds.map( ( id ) => [
				id,
				createPlaceholderResponseItem( id ),
			] )
		);
	}, [ entitiesReady, placeholderIds ] );

	return { blockContexts, placeholderProductMap, isReady: entitiesReady };
};
