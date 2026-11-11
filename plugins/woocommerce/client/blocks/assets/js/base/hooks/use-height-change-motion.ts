/**
 * External dependencies
 */
import { useRefEffect } from '@wordpress/compose';
import {
	animateHeightChange,
	isAnimating,
} from '@woocommerce/base-utils/collapse';
import { isSwapHolding } from '@woocommerce/base-utils/swap-sections';

// CSS properties that change how much space a box takes.
const isSizeProperty = ( property: string ) =>
	property.includes( 'height' ) ||
	property.startsWith( 'margin' ) ||
	property.startsWith( 'padding' ) ||
	( property.startsWith( 'border' ) && property.endsWith( 'idth' ) );

// Whether an animation changes sizes, such as the collapse helpers or a panel button's margin transition. Fades,
// slides, CSS keyframe animations, and transitions of `transform` such as a price roll, don't.
const changesSize = ( animation: Animation ) => {
	if ( animation instanceof CSSAnimation ) {
		return false;
	}
	if ( animation instanceof CSSTransition ) {
		return isSizeProperty( animation.transitionProperty );
	}
	const effect = animation.effect;
	return (
		effect instanceof KeyframeEffect &&
		effect
			.getKeyframes()
			.some( ( keyframe ) =>
				Object.keys( keyframe ).some( isSizeProperty )
			)
	);
};

/**
 * Returns a ref that eases its element's height when what's inside changes size, such as a totals row whose
 * description gains a line, so what follows moves instead of jumping. A change in width, such as resizing the
 * window, applies at once, and so does any change while the element, or something inside it such as a panel, grows
 * in or collapses: that motion already moves what follows.
 */
export const useHeightChangeMotion = () =>
	useRefEffect( ( element: HTMLElement ) => {
		if (
			typeof ResizeObserver === 'undefined' ||
			typeof MutationObserver === 'undefined' ||
			typeof element.getAnimations !== 'function'
		) {
			return;
		}
		let last = element.getBoundingClientRect();
		// While a swap waits for its content to settle, a change in size is part of the swap.
		const isMoving = () =>
			isSwapHolding( element ) ||
			isAnimating( element ) ||
			element
				.getAnimations( { subtree: true } )
				.some(
					( animation ) =>
						animation.playState === 'running' &&
						changesSize( animation )
				);
		// Keeps track of the element's size, whatever changes it. Starting an animation from here would resize the
		// element again within the frame, which the browser reports as a ResizeObserver loop error.
		const sizes = new ResizeObserver( () => {
			last = element.getBoundingClientRect();
		} );
		// Content changes, such as React rendering a new description, arrive here before layout and paint, so the
		// animation starts from the old height without a frame of the new.
		const changes = new MutationObserver( () => {
			const size = element.getBoundingClientRect();
			if (
				! isMoving() &&
				size.width === last.width &&
				Math.abs( size.height - last.height ) >= 1
			) {
				animateHeightChange( element, last.height );
			}
			last = size;
		} );
		sizes.observe( element );
		changes.observe( element, {
			childList: true,
			subtree: true,
			characterData: true,
			attributeFilter: [ 'class', 'hidden' ],
		} );
		return () => {
			sizes.disconnect();
			changes.disconnect();
		};
	}, [] );
