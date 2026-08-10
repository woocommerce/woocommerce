/**
 * Legacy classic Add to Cart form integration.
 *
 * The classic single-product variation form (`add-to-cart-variation.js`)
 * is jQuery-driven and emits its variation lifecycle as jQuery custom
 * events. This module isolates that integration so the main gallery
 * frontend stays free of jQuery type acrobatics.
 *
 * Returns a teardown callable when subscription succeeds, or `null` when
 * jQuery isn't loaded on the page so the caller can fall back to a
 * MutationObserver-based path.
 */

/**
 * External dependencies
 */
import { withScope } from '@wordpress/interactivity';

/**
 * Internal dependencies
 */
import { LEGACY_FORM_JQUERY_EVENTS } from './constants';
import type {
	LegacyJQueryFormHandlers,
	LegacyJQueryWindow,
	LegacyVariationPayload,
} from './types';

/** A positive integer ID from the variation event payload. */
const isValidId = ( id: unknown ): id is number =>
	typeof id === 'number' && Number.isInteger( id ) && id > 0;

/**
 * Subscribe to the legacy classic Add to Cart form's jQuery variation
 * events. Returns a teardown callable, or `null` when jQuery isn't
 * loaded on the page.
 */
export const subscribeLegacyJQueryFormVariations = (
	formElement: HTMLElement,
	handlers: LegacyJQueryFormHandlers
): null | ( () => void ) => {
	const legacyJQuery = ( window as LegacyJQueryWindow ).jQuery;
	if ( ! legacyJQuery ) {
		return null;
	}

	const $form = legacyJQuery( formElement );

	const handleFound = withScope(
		( _event?: unknown, variation?: LegacyVariationPayload ) => {
			if (
				isValidId( variation?.variation_id ) ||
				isValidId( variation?.image_id )
			) {
				handlers.onVariationFound(
					variation?.variation_id,
					variation?.image_id
				);
				return;
			}

			handlers.onVariationReset();
		}
	);

	const handleReset = withScope( () => handlers.onVariationReset() );

	$form
		.on( LEGACY_FORM_JQUERY_EVENTS.foundVariation, handleFound )
		.on( LEGACY_FORM_JQUERY_EVENTS.hideOrResetVariation, handleReset );

	return () => {
		$form.off( LEGACY_FORM_JQUERY_EVENTS.namespace );
	};
};
