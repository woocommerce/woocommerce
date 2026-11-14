/**
 * External dependencies
 */
import { useLayoutEffect, useRef } from '@wordpress/element';
import { animateCollapse, collapseCopy } from '@woocommerce/base-utils';

/**
 * For content that a control shows and hides, such as a checkbox that reveals a field. After a click or tap, the
 * content grows in the real layout when it shows. When it goes, it unmounts at once, so that its fields stop counting,
 * and an inert copy collapses in its place. A key press, page load, and changes in code apply immediately.
 *
 * Call `onToggle` in the handler of the control, before the state changes. Pass the click event when there is one: a
 * click that a key press starts has no click count. Otherwise, put `triggerRef` on the control: a key press leaves
 * focus visible on it, and a click does not.
 *
 * @param isShown                 Whether the content shows.
 * @param getElement              Returns the element that grows and collapses. Give it the `motion-collapse` mixin.
 *                                Find it by id, or skip `[data-wc-collapse-copy]`: the copy keeps the element's classes
 *                                and comes first, so a class selector alone finds the copy while it collapses.
 * @param options
 * @param options.growFromTrigger Set to `true` when the content replaces the control, so that it grows from the height
 *                                of the control, and the content below moves in one direction.
 * @return The ref for the control, and the function to call when the shopper uses it.
 */
export const useToggleCollapse = < E extends HTMLElement = HTMLInputElement >(
	isShown: boolean,
	getElement: () => HTMLElement | null,
	{ growFromTrigger = false }: { growFromTrigger?: boolean } = {}
) => {
	const triggerRef = useRef< E >( null );
	const animateRef = useRef( false );
	const fromHeightRef = useRef< number | null >( null );
	// The element is read when the motion starts, not when the component renders.
	const getElementRef = useRef( getElement );
	getElementRef.current = getElement;

	useLayoutEffect( () => {
		if ( isShown ) {
			animateCollapse(
				getElementRef.current(),
				true,
				animateRef.current,
				fromHeightRef.current === null
					? undefined
					: { height: `${ fromHeightRef.current }px`, opacity: '0' }
			);
		}
		animateRef.current = false;
		fromHeightRef.current = null;
	}, [ isShown ] );

	const onToggle = ( event?: {
		detail: number;
		currentTarget: EventTarget | null;
	} ) => {
		const trigger = event ? event.currentTarget : triggerRef.current;
		animateRef.current =
			trigger instanceof HTMLElement &&
			( event
				? event.detail !== 0
				: ! trigger.matches( ':focus-visible' ) );
		fromHeightRef.current =
			animateRef.current &&
			growFromTrigger &&
			trigger instanceof HTMLElement
				? trigger.offsetHeight
				: null;
		if ( isShown && animateRef.current ) {
			collapseCopy( getElementRef.current() );
		}
	};

	return { triggerRef, onToggle };
};
