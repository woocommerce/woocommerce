/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

interface MarketplaceViewProps {
	view: string;
	search_term?: string;
	product_type?: string;
	category?: string;
}

interface LegacyTabViewProps {
	view: string;
	search_term?: string;
	category?: string;
}

/**
 * Record a marketplace view event.
 * This is a new event that is easier to understand and implement consistently
 */
function recordMarketplaceView( props: MarketplaceViewProps ) {
	const eventProps = { ...props };

	recordEvent( 'marketplace_view', eventProps );
}

/**
 * Ensure we still have legacy events in place
 * the "view" prop maps to a "section" prop in the event for compatibility with old funnels.
 *
 * @param props The props object containing view, search_term, section, and category.
 */
function recordLegacyTabView( props: LegacyTabViewProps ) {
	let oldEventName = 'extensions_view';
	const oldEventProps: {
		section: string | undefined;
		search_term?: string;
		version: string;
	} = { ...props, section: '', version: '2' };

	if ( props.view === 'discover' ) {
		oldEventName = 'extensions_view';
		oldEventProps.section = '_featured';
	}

	if ( props.view === 'extensions' ) {
		oldEventName = 'extensions_view';
		oldEventProps.section = props?.category;

		if ( ! props.category ) {
			oldEventProps.section = '_all';
		}
	}

	if ( props.view === 'themes' ) {
		oldEventName = 'extensions_view';
		oldEventProps.section = 'themes';
	}

	if ( props.view === 'search' ) {
		oldEventName = 'extensions_view_search';
		oldEventProps.section = props.view;
		oldEventProps.search_term = props.search_term;
	}

	if ( props.view === 'my-subscriptions' ) {
		oldEventName = 'subscriptions_view';
		oldEventProps.section = 'helper';
	}

	recordEvent( oldEventName, oldEventProps );
}

export { recordMarketplaceView, recordLegacyTabView };
