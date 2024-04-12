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
import QueryString, { parse } from 'qs';

/**
 * Internal dependencies
 */
import { EmbeddedProductBodyProps } from './embedded-product-page-layout-props';
import PaymentRecommendations from '~/payments/payment-recommendations';
import ShippingRecommendations from '~/shipping/shipping-recommendations';
import { StoreAddressTour } from '~/guided-tours/store-address-tour';
import ProductPage from '~/products/product-page';

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
				console.log( 'loaded data' );
				setLoaded( true );
			} );
		} else {
			console.log( 'wpLoadBlockEditor does not exist' );
			setLoaded( true );
		}
	}, [] );

	const query = parse( location.search.substring( 1 ) );
	let queryParams: QueryParams = { page: '', tab: '' };
	if ( isWPPage( query ) ) {
		queryParams = query;
	}

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
