/**
 * External dependencies
 */
import { useLayoutEffect, useRef } from '@wordpress/element';
import {
	collapseCopyOut,
	collapseIn,
	getPlace,
	type Place,
} from '@woocommerce/base-utils/collapse';
import { wasRecentPointerInput } from '@woocommerce/base-utils/input-modality';
import { isInSwap } from '@woocommerce/base-utils/swap-sections';

/**
 * Internal dependencies
 */
import { useHeightChangeMotion } from './use-height-change-motion';

/**
 * For a section that shows, goes or is swapped for another because the shopper changed something, such as ticking
 * "Use same address for billing". `shown` is what's showing (`true`, or a key such as the selected option; falsy for
 * nothing), and `getElement` returns its element. When `shown` changes, a collapsing copy takes the old section's
 * place and the new one grows in, so the content after them moves instead of jumping. Only changes straight after a
 * click or tap animate; on page load and after a key press they apply at once. A change that's part of a swap of
 * several sections, such as Ship/Pickup (see `playSwap`), is left to the swap. Call it from a component that stays
 * mounted.
 *
 * With `easeHeight`, the section's height also eases when its content changes size later, such as pickup locations
 * that load after the section appears.
 */
export const useSectionCollapse = (
	shown: unknown,
	getElement: () => Element | null,
	{ easeHeight = false }: { easeHeight?: boolean } = {}
) => {
	const place = useRef< Place | null >( null );
	const lastShown = useRef( shown );
	const heightMotion = useHeightChangeMotion();

	useLayoutEffect( () => {
		const element = shown ? getElement() : null;
		const isSwapping =
			isInSwap( element ) ||
			isInSwap( place.current?.parent as Element | undefined );
		if (
			shown !== lastShown.current &&
			wasRecentPointerInput() &&
			! isSwapping
		) {
			if ( place.current ) {
				collapseCopyOut( place.current );
			}
			collapseIn( element );
		}
		if ( easeHeight && element !== place.current?.element ) {
			heightMotion( element instanceof HTMLElement ? element : null );
		}
		lastShown.current = shown;
		place.current = getPlace( element );
	} );

	useLayoutEffect( () => () => heightMotion( null ), [ heightMotion ] );
};
