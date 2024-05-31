/**
 * External dependencies
 */
import { useState } from 'react';

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

	const updatePopoverPosition = ( {
		mainBodyWidth,
		iframeWidth,
		event,
		clickedBlockClientId,
		hoveredBlockClientId,
	}: {
		mainBodyWidth: number;
		iframeWidth: number;
		event: MouseEvent;
		clickedBlockClientId: string | null;
		hoveredBlockClientId: string | null;
	} ) => {
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
			const newElement = {
				getBoundingClientRect: generateGetBoundingClientRect(
					event.clientX + ( mainBodyWidth - iframeWidth ),
					event.clientY + 40
				),
			} as VirtualElement;

			setVirtualElement( newElement );
			return;
		}

		setPopoverStatus( PopoverStatus.HIDDEN );
		clickedClientId = null;
	};

	return [ popoverStatus, virtualElement, updatePopoverPosition ] as const;
};
