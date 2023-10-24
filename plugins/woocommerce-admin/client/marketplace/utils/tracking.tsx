/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

interface MarketplaceViewProps {
	view?: string;
	search_term?: string;
	product_type?: string;
	category?: string;
}

/**
 * Record a marketplace view event.
 * This is a new event that is easier to understand and implement consistently
 */
function recordMarketplaceView( props: MarketplaceViewProps ) {
	// The category prop changes to a blank string on first viewing all products after a search.
	// This is undesirable and causes a duplicate event that will artifically inflate event counts.
	if ( props.category === '' ) {
		return;
	}

	const view = props.view || null;
	const search_term = props.search_term || null;
	const product_type = props.product_type || null;
	const category = props.category || null;

	const eventProps = {
		...( view && { view } ),
		...( search_term && { search_term } ),
		...( product_type && { product_type } ),
		...( category && { category } ),
	};

	if (
		view &&
		[ 'extensions', 'themes', 'search' ].includes( view ) &&
		! category
	) {
		eventProps.category = '_all';
	}

	recordEvent( 'marketplace_view', eventProps );
}

/**
 * Ensure we still have legacy events in place
 * the "view" prop maps to a "section" prop in the event for compatibility with old funnels.
 *
 * @param props The props object containing view, search_term, section, and category.
 */
function recordLegacyTabView( props: MarketplaceViewProps ) {
	// We skip blank views (initial mount) and product_type will artificially inflate legacy event counts.
	if ( ! props.view || props.product_type ) {
		return;
	}
	let oldEventName = 'extensions_view';
	const section = props.view || null;
	const search_term = props.search_term || null;
	const category = props.category || null;

	const oldEventProps = {
		...( section && { section } ),
		...( search_term && { search_term } ),
		version: '2',
	};

	switch ( section ) {
		case 'discover':
			oldEventName = 'extensions_view';
			oldEventProps.section = '_featured';
			break;
		case 'extensions':
			oldEventName = 'extensions_view';
			oldEventProps.section = category || '_all';
			break;
		case 'themes':
			oldEventName = 'extensions_view';
			oldEventProps.section = 'themes';
			break;
		case 'search':
			oldEventName = 'extensions_view_search';
			oldEventProps.section = section;
			oldEventProps.search_term = search_term || '';
			break;
		case 'my-subscriptions':
			oldEventName = 'subscriptions_view';
			oldEventProps.section = 'helper';
			break;
		default:
			break;
	}

	recordEvent( oldEventName, oldEventProps );
}

export { recordMarketplaceView, recordLegacyTabView };
