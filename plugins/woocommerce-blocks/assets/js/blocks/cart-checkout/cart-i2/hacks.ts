/**
 * HACKS
 *
 * This file contains functionality to "lock" blocks i.e. to prevent blocks being moved or deleted. This needs to be
 * kept in place until native support for locking is available in WordPress (estimated WordPress 5.9).
 */

/**
 * @todo Remove custom block locking (requires native WordPress support)
 */

/**
 * External dependencies
 */
import {
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { isTextField } from '@wordpress/dom';
import { useSelect, subscribe, select as _select } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import { MutableRefObject } from 'react';
import { BACKSPACE, DELETE } from '@wordpress/keycodes';
import { hasFilter } from '@wordpress/hooks';
/**
 * Toggle class on body.
 *
 * @param {string} className CSS Class name.
 * @param {boolean} add True to add, false to remove.
 */
const toggleBodyClass = ( className: string, add = true ) => {
	if ( add ) {
		window.document.body.classList.add( className );
	} else {
		window.document.body.classList.remove( className );
	}
};

/**
 * addClassToBody
 *
 * This components watches the current selected block and adds a class name to the body if that block is locked. If the
 * current block is not locked, it removes the class name. The appended body class is used to hide UI elements to prevent
 * the block from being deleted.
 *
 * We use a component so we can react to changes in the store.
 */
export const addClassToBody = (): void => {
	if ( ! hasFilter( 'blocks.registerBlockType', 'core/lock/addAttribute' ) ) {
		subscribe( () => {
			const blockEditorSelect = _select( blockEditorStore );

			if ( ! blockEditorSelect ) {
				return;
			}

			const selectedBlock = blockEditorSelect.getSelectedBlock();

			if ( ! selectedBlock ) {
				return;
			}

			toggleBodyClass(
				'wc-lock-selected-block--remove',
				!! selectedBlock?.attributes?.lock?.remove
			);

			toggleBodyClass(
				'wc-lock-selected-block--move',
				!! selectedBlock?.attributes?.lock?.move
			);
		} );
	}
};

/**
 * This is a hook we use in conjunction with useBlockProps. Its goal is to check if a block is locked (move or remove)
 * and will stop the keydown event from propagating to stop it from being deleted via the keyboard.
 *
 * @todo Disable custom locking support if native support is detected.
 */
const useLockBlock = ( {
	clientId,
	ref,
	attributes,
}: {
	clientId: string;
	ref: MutableRefObject< Element | undefined >;
	attributes: Record< string, unknown >;
} ): void => {
	const lockInCore = hasFilter(
		'blocks.registerBlockType',
		'core/lock/addAttribute'
	);
	const { isSelected } = useSelect(
		( select ) => {
			return {
				isSelected: select( blockEditorStore ).isBlockSelected(
					clientId
				),
			};
		},
		[ clientId ]
	);

	const node = ref.current;

	return useEffect( () => {
		if ( ! isSelected || ! node || lockInCore ) {
			return;
		}
		function onKeyDown( event: KeyboardEvent ) {
			const { keyCode, target } = event;
			if ( keyCode !== BACKSPACE && keyCode !== DELETE ) {
				return;
			}

			if ( target !== node || isTextField( target ) ) {
				return;
			}
			// Prevent the keyboard event from propogating if it supports locking.
			if ( attributes?.lock?.remove ) {
				event.preventDefault();
				event.stopPropagation();
			}
		}

		node.addEventListener( 'keydown', onKeyDown, true );

		return () => {
			node.removeEventListener( 'keydown', onKeyDown, true );
		};
	}, [ node, isSelected, lockInCore, attributes ] );
};

/**
 * This hook is a light wrapper to useBlockProps, it wraps that hook plus useLockBlock to pass data between them.
 */
export const useBlockPropsWithLocking = (
	props: Record< string, unknown > = {}
): Record< string, unknown > => {
	const ref = useRef< Element >();
	const { attributes } = props;
	const blockProps = useBlockProps( { ref, ...props } );
	useLockBlock( {
		ref,
		attributes,
		clientId: blockProps[ 'data-block' ],
	} );
	return blockProps;
};
