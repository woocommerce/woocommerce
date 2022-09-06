/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Fill, Popover } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { debounce } from 'lodash';
import { useCallback, useEffect, useState, useRef } from 'react';

/**
 * Internal dependencies
 */
import { ContextFormattingToolbar } from './context-formatting-toolbar';
import { isCurrentSelectionWithinEditor } from '../utils/selection';

export const CONTEXT_TOOLBAR_SLOT_NAME = 'dayone/context-toolbar';

export const ContextToolbar = () => {
	const [ selectionBounds, setSelectionBounds ] = useState< DOMRect | null >(
		null
	);
	const isMounted = useRef( true );

	useEffect( () => {
		const debouncedSelectionHandler = debounce( () => {
			const selection = window.getSelection();
			const mounted = isMounted.current;

			if ( selection?.anchorNode ) {
				const range = selection?.getRangeAt( 0 );
				const bounds = range?.getBoundingClientRect();

				if (
					range &&
					range.endOffset - range.startOffset < 1 &&
					mounted
				) {
					setSelectionBounds( null );
				} else if ( bounds && mounted ) {
					setSelectionBounds( bounds );
				}
			} else if ( mounted ) {
				setSelectionBounds( null );
			}
		}, 150 );

		const updateSelectionBoundsForScroll = () => {
			const mounted = isMounted.current;
			// don't thrash re-positioning of the popover
			window.requestAnimationFrame( () => {
				const selection = window.getSelection();

				if ( selection?.anchorNode ) {
					const range = selection?.getRangeAt( 0 );
					const bounds = range?.getBoundingClientRect();
					if ( bounds && mounted ) {
						setSelectionBounds( bounds );
					}
				} else if ( mounted ) {
					setSelectionBounds( null );
				}
			} );
		};

		// on scroll make sure the popover is correctly placed.
		document.addEventListener(
			'scroll',
			updateSelectionBoundsForScroll,
			true
		);
		document.addEventListener(
			'selectionchange',
			debouncedSelectionHandler,
			true
		);

		return () => {
			isMounted.current = false;
			document.removeEventListener(
				'scroll',
				updateSelectionBoundsForScroll
			);
			document.removeEventListener(
				'selectionchange',
				debouncedSelectionHandler
			);
		};
	}, [] );

	const isMultiCharacterSelection = useSelect(
		( select ) => {
			const selectEnd = select( 'core/block-editor' ).getSelectionEnd();
			const selectBegin =
				select( 'core/block-editor' ).getSelectionStart();
			const multiSelectedBlocks =
				select( 'core/block-editor' ).getMultiSelectedBlocks();

			const beginOffset = selectBegin?.offset;
			const endOffset = selectEnd?.offset;

			if ( beginOffset !== undefined && endOffset !== undefined ) {
				return endOffset > beginOffset && ! multiSelectedBlocks.length;
			}

			return false;
		},
		[ selectionBounds ]
	);

	const getAnchorRect = useCallback( () => {
		// 80 is a magic number
		const heightOffset = 80;
		return DOMRect.fromRect( {
			// getAnchorRect is only called when selectionBounds is defined, so we can use ! below
			width: selectionBounds!.width,
			height: selectionBounds!.height,
			x: selectionBounds!.x,
			y: selectionBounds!.y - heightOffset,
		} );
	}, [ selectionBounds ] );

	return isMultiCharacterSelection &&
		selectionBounds &&
		isCurrentSelectionWithinEditor() ? (
		// We can't pass emotion styles into popover, so we're forced to target child styles
		// to ensure this displays correctly. This override also exists in Gberg's BlockTools.
		<Fill name={ 'TODO_REPLACE_SLOT_NAME' }>
			<div>
				<Popover
					// @ts-ignore
					__unstableSlotName={ SLOT_NAME }
					focusOnMount={ false }
					animate={ false }
					// @ts-ignore
					getAnchorRect={ getAnchorRect }
				>
					<ContextFormattingToolbar />
				</Popover>
			</div>
		</Fill>
	) : null;
};
