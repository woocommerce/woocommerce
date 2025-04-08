//  TODO - move this to shared code once the product collection block is converted to use script modules.
/**
 * Internal dependencies
 */
import { CoreCollectionNames } from './types';

interface DispatchedEventProperties {
	// Whether the event bubbles.
	bubbles?: boolean;
	// Whether the event is cancelable.
	cancelable?: boolean;
	// See https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/detail
	detail?: unknown;
	// Element that dispatches the event. By default, the body.
	element?: Element | null;
}

/**
 * Wrapper function to dispatch an event.
 */
export const dispatchEvent = (
	name: string,
	{
		bubbles = false,
		cancelable = false,
		element,
		detail = {},
	}: DispatchedEventProperties
): void => {
	if ( ! CustomEvent ) {
		return;
	}
	if ( ! element ) {
		element = document.body;
	}
	const event = new CustomEvent( name, {
		bubbles,
		cancelable,
		detail,
	} );
	element.dispatchEvent( event );
};

export const triggerProductListRenderedEvent = ( payload: {
	collection?: CoreCollectionNames | string;
} ) => {
	dispatchEvent( 'wc-blocks_product_list_rendered', {
		bubbles: true,
		cancelable: true,
		detail: payload,
	} );
};

export const triggerViewedProductEvent = ( payload: {
	collection?: CoreCollectionNames | string;
	productId: number;
} ): void => {
	dispatchEvent( 'wc-blocks_viewed_product', {
		bubbles: true,
		cancelable: true,
		detail: payload,
	} );
};
