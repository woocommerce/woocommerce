/**
 * External dependencies
 */
import { useContainerWidthContext } from '@woocommerce/base-context';
import { useCallback, useId, useState } from '@wordpress/element';
import type { HTMLAttributes, KeyboardEvent } from 'react';

export type OrderSummaryToggle = {
	isOpen: boolean;
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
 * @param {Function} applyToggle Applies the shopper's toggle, given the update and whether the summary was open.
 * @return {OrderSummaryToggle} Open state, the measured size, the id the bar
 *                              controls, and the props to spread on the bar.
 */
export const useOrderSummaryToggle = (
	applyToggle: ( toggle: () => void, wasOpen: boolean ) => void = (
		toggle
	) => toggle()
): OrderSummaryToggle => {
	const { hasContainerWidth, isLarge } = useContainerWidthContext();
	const [ isOpen, setIsOpen ] = useState( false );
	const ariaControlsId = useId();
	const closeSummary = useCallback( () => setIsOpen( false ), [] );

	const toggleProps = ! isLarge
		? {
				role: 'button',
				onClick: () =>
					applyToggle( () => setIsOpen( ! isOpen ), isOpen ),
				'aria-expanded': isOpen,
				'aria-controls': ariaControlsId,
				tabIndex: 0,
				onKeyDown: ( event: KeyboardEvent ) => {
					if ( event.key === 'Enter' || event.key === ' ' ) {
						// Space scrolls the page and Enter submits the
						// surrounding checkout form. A real button would
						// suppress both for us.
						event.preventDefault();
						applyToggle( () => setIsOpen( ! isOpen ), isOpen );
					}
				},
			}
		: {};

	return {
		isOpen,
		hasContainerWidth,
		isLarge,
		closeSummary,
		ariaControlsId,
		toggleProps,
	};
};
