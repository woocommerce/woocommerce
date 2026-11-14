/**
 * External dependencies
 */
import { useContainerWidthContext } from '@woocommerce/base-context';
import { useCallback, useId, useState } from '@wordpress/element';
import type { HTMLAttributes, KeyboardEvent, MouseEvent } from 'react';

export type OrderSummaryToggle = {
	isOpen: boolean;
	// Whether a click or tap made the last change of `isOpen`. Key presses and closing in code apply immediately.
	isClickToggle: boolean;
	hasContainerWidth: boolean;
	isLarge: boolean;
	closeSummary: () => void;
	ariaControlsId: string;
	toggleProps: HTMLAttributes< HTMLDivElement >;
};

/**
 * State and props for the collapsible "Order summary" bar, shared by the
 * frontend block and the editor preview so the two can't drift apart.
 *
 * The interactive props are attached when the container is not large, rather
 * than by listing the smaller sizes: the width is unknown until the resize
 * observer first reports, and the CSS has already collapsed the summary by
 * then. Assuming the collapsed presentation while unsure keeps the bar
 * operable; the reverse leaves it inert with no other way to see the totals.
 *
 * @return {OrderSummaryToggle} Open state, the measured size, the id the bar
 *                              controls, and the props to spread on the bar.
 */
export const useOrderSummaryToggle = (): OrderSummaryToggle => {
	const { hasContainerWidth, isLarge } = useContainerWidthContext();
	const [ { isOpen, isClickToggle }, setToggle ] = useState( {
		isOpen: false,
		isClickToggle: false,
	} );
	const ariaControlsId = useId();
	const closeSummary = useCallback(
		() => setToggle( { isOpen: false, isClickToggle: false } ),
		[]
	);

	const toggleProps = ! isLarge
		? {
				role: 'button',
				// A click that a key press or assistive technology starts has no click count.
				onClick: ( event: MouseEvent ) =>
					setToggle( {
						isOpen: ! isOpen,
						isClickToggle: event.detail !== 0,
					} ),
				'aria-expanded': isOpen,
				'aria-controls': ariaControlsId,
				tabIndex: 0,
				onKeyDown: ( event: KeyboardEvent ) => {
					if ( event.key === 'Enter' || event.key === ' ' ) {
						// Space scrolls the page and Enter submits the
						// surrounding checkout form. A real button would
						// suppress both for us.
						event.preventDefault();
						setToggle( { isOpen: ! isOpen, isClickToggle: false } );
					}
				},
			}
		: {};

	return {
		isOpen,
		isClickToggle,
		hasContainerWidth,
		isLarge,
		closeSummary,
		ariaControlsId,
		toggleProps,
	};
};
