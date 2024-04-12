/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import { useEffect, useState } from '@wordpress/element';
import { triggerExitPageCesSurvey } from '@woocommerce/customer-effort-score';
import {
	LayoutContextProvider,
	getLayoutContextValue,
} from '@woocommerce/admin-layout';
import { SlotFillProvider } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { getQuery } from '@woocommerce/navigation';
import QueryString, { parse } from 'qs';

/**
 * Internal dependencies
 */
import { PaymentRecommendations } from '../payments';
import { ShippingRecommendations } from '../shipping';
import { StoreAddressTour } from '../guided-tours/store-address-tour';
import { EmbeddedProductBodyProps } from './embedded-product-page-layout-props';
import ProductPage from './product-page';

type QueryParams = EmbeddedProductBodyProps;

function isWPPage(
	params: QueryParams | QueryString.ParsedQs
): params is QueryParams {
	return ( params as QueryParams ).page !== undefined;
}

const EMBEDDED_BODY_COMPONENT_LIST: React.ElementType[] = [
	PaymentRecommendations,
	ShippingRecommendations,
	StoreAddressTour,
];

console.log( 'apiFetch middleware ' );

const routeMatchers = [
	{
		matcher: new RegExp( '^/wp/v2/product(?!_)' ),
		getReplaceString: () => '/wc/v3/products',
	},
	{
		matcher: new RegExp( '^/wp/v2/product_variation' ),
		replacement: '/wc/v3/products/0/variations',
		getReplaceString: () => {
			const query = getQuery() as { path: string };
			const variationMatcher = new RegExp(
				'/product/([0-9]+)/variation/([0-9]+)'
			);
			const matched = ( query.path || '' ).match( variationMatcher );
			if ( matched && matched.length === 3 ) {
				return '/wc/v3/products/' + matched[ 1 ] + '/variations';
			}
			return '/wc/v3/products/0/variations';
		},
	},
];
apiFetch.use( ( options, next ) => {
	if ( options.path ) {
		for ( const { matcher, getReplaceString } of routeMatchers ) {
			if ( matcher.test( options.path ) ) {
				options.path = options.path.replace(
					matcher,
					getReplaceString()
				);
				break;
			}
		}
	}
	return next( options );
} );

/**
 * This component is appended to the bottom of the WooCommerce non-react pages (like settings).
 * You can add a component by writing a Fill component from slot-fill with the `embedded-body-layout` name.
 *
 * Each Fill component receives QueryParams, consisting of a page, tab, and section string.
 */
export const EmbeddedProductPageLayout = () => {
	const [ loaded, setLoaded ] = useState( false );
	useEffect( () => {
		triggerExitPageCesSurvey();
		if ( window._wpLoadBlockEditor ) {
			window._wpLoadBlockEditor.then( () => {
				setLoaded( true );
			} );
		} else {
			setLoaded( true );
		}
	}, [] );

	const query = parse( location.search.substring( 1 ) );
	let queryParams: QueryParams = { page: '', tab: '' };
	if ( isWPPage( query ) ) {
		queryParams = query;
	}
	/**
	 * Filter an array of body components for WooCommerce non-react pages.
	 *
	 * @filter woocommerce_admin_embedded_layout_components
	 * @param {Array.<Node>} embeddedBodyComponentList Array of body components.
	 * @param {Object}       query                     url query parameters.
	 */
	const componentList = applyFilters(
		'woocommerce_admin_embedded_layout_components',
		EMBEDDED_BODY_COMPONENT_LIST,
		queryParams
	) as React.ElementType< EmbeddedProductBodyProps >[];

	return (
		<LayoutContextProvider value={ getLayoutContextValue( [ 'page' ] ) }>
			<SlotFillProvider>
				<div
					className="woocommerce-embedded-layout__primary"
					id="woocommerce-embedded-layout__primary"
				>
					{ loaded && <ProductPage /> }
				</div>
			</SlotFillProvider>
		</LayoutContextProvider>
	);
};
