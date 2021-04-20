/**
 * External dependencies
 */
import { applyFilters } from '@wordpress/hooks';
import QueryString, { parse } from 'qs';

/**
 * Internal dependencies
 */
import { PaymentRecommendations } from '../payments';
import { EmbeddedBodyProps } from './embedded-body-props';
import './style.scss';

type QueryParams = EmbeddedBodyProps;

function isWPPage(
	params: QueryParams | QueryString.ParsedQs
): params is QueryParams {
	return ( params as QueryParams ).page !== undefined;
}

const EMBEDDED_BODY_COMPONENT_LIST: React.ElementType[] = [
	PaymentRecommendations,
];

/**
 * This component is appended to the bottom of the WooCommerce non-react pages (like settings).
 * You can add a component by writing a Fill component from slot-fill with the `embedded-body-layout` name.
 *
 * Each Fill component receives QueryParams, consisting of a page, tab, and section string.
 */
export const EmbeddedBodyLayout = () => {
	const query = parse( location.search.substring( 1 ) );
	let queryParams: QueryParams = { page: '', tab: '' };
	if ( isWPPage( query ) ) {
		queryParams = query;
	}
	const componentList = applyFilters(
		'woocommerce_admin_embedded_layout_components',
		EMBEDDED_BODY_COMPONENT_LIST,
		queryParams
	) as React.ElementType< EmbeddedBodyProps >[];

	return (
		<div
			className="woocommerce-embedded-layout__primary"
			id="woocommerce-embedded-layout__primary"
		>
			{ componentList.map( ( Comp, index ) => {
				return <Comp key={ index } { ...queryParams } />;
			} ) }
		</div>
	);
};
