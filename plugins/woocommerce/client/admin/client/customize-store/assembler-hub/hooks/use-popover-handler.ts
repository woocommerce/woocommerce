/**
 * External dependencies
 */
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { ENABLE_CLICK_CLASS } from './auto-block-preview-event-listener';

export enum PopoverStatus {
	VISIBLE = 'VISIBLE',
	HIDDEN = 'HIDDEN',
}

type VirtualElement = Pick< Element, 'getBoundingClientRect' >;

const generateGetBoundingClientRect = ( x = 0, y = 0 ) => {
	return () => ( {
		width: 0,
		height: 0,
		top: y,
		right: x,
		bottom: y,
		left: x,
	} );
};

let clickedClientId: string | null = null;
let hoveredClientId: string | null = null;

export const usePopoverHandler = () => {
	const [ popoverStatus, setPopoverStatus ] = useState< PopoverStatus >(
		PopoverStatus.HIDDEN
	);

	const defaultVirtualElement = {
		getBoundingClientRect: generateGetBoundingClientRect(),
	} as VirtualElement;

	const [ virtualElement, setVirtualElement ] = useState< VirtualElement >(
		defaultVirtualElement
	);

	const hidePopover = () => {
		setPopoverStatus( PopoverStatus.HIDDEN );
		clickedClientId = null;
		hoveredClientId = null;
	};

	const updatePopoverPosition = ( {
		event,
		clickedBlockClientId,
		hoveredBlockClientId,
	}: {
		event: MouseEvent;
		clickedBlockClientId: string | null;
		hoveredBlockClientId: string | null;
	} ) => {
		const iframe = window.document.querySelector(
			'.woocommerce-customize-store-assembler > iframe[name="editor-canvas"]'
		) as HTMLElement;

		const target = event.target as HTMLElement;

		// If the hover event is over elements with an ENABLE_CLICK_CLASS, hide the popover.
		// This is because it's likely the "No Blocks" placeholder and we don't want the popover to show since its interactive.
		if ( target.classList.contains( ENABLE_CLICK_CLASS ) ) {
			hidePopover();
			return;
		}

		clickedClientId =
			clickedBlockClientId === null
				? clickedClientId
				: clickedBlockClientId;
		hoveredClientId =
			hoveredBlockClientId === null
				? hoveredClientId
				: hoveredBlockClientId;

		if ( clickedClientId === hoveredClientId ) {
			if ( popoverStatus === PopoverStatus.HIDDEN ) {
				setPopoverStatus( PopoverStatus.VISIBLE );
			}

			const iframeRect = iframe.getBoundingClientRect();

			const newElement = {
				getBoundingClientRect: generateGetBoundingClientRect(
					event.clientX + iframeRect.left,
					event.clientY + iframeRect.top + 20
				),
			} as VirtualElement;

			setVirtualElement( newElement );
			return;
		}

		setPopoverStatus( PopoverStatus.HIDDEN );
		clickedClientId = null;
	};

	return [
		popoverStatus,
		virtualElement,
		updatePopoverPosition,
		hidePopover,
		setPopoverStatus,
	] as const;
};
