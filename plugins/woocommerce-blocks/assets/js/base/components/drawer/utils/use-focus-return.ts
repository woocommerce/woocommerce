/**
 * Some code of the Drawer component is based on the Modal component from Gutenberg:
 * https://github.com/WordPress/gutenberg/blob/trunk/packages/components/src/modal/index.tsx
 */
/**
 * External dependencies
 */
import { useEffect, useRef, useCallback } from '@wordpress/element';

let origin: Element | null = null;

/**
 * Adds the unmount behavior of returning focus to the element which had it
 * previously as is expected for roles like menus or dialogs.
 *
 * This function is copied from Gutenberg's hook under the same name.
 */
export default function useFocusReturn(
	onFocusReturn?: () => void
): ( node: HTMLElement | null ) => void {
	/** @type {import('react').MutableRefObject<null | HTMLElement>} */
	const ref = useRef< HTMLElement >( null );
	/** @type {import('react').MutableRefObject<null | Element>} */
	const focusedBeforeMount = useRef< Element >( null );
	const onFocusReturnRef = useRef( onFocusReturn );
	useEffect( () => {
		onFocusReturnRef.current = onFocusReturn;
	}, [ onFocusReturn ] );

	return useCallback( ( node ) => {
		if ( node ) {
			// Set ref to be used when unmounting.
			ref.current = node;

			// Only set when the node mounts.
			if ( focusedBeforeMount.current ) {
				return;
			}

			focusedBeforeMount.current = node.ownerDocument.activeElement;
		} else if ( focusedBeforeMount.current ) {
			const isFocused = ref.current?.contains(
				ref.current?.ownerDocument.activeElement
			);

			if ( ref.current?.isConnected && ! isFocused ) {
				origin ??= focusedBeforeMount.current;
				// This return would cause the code to never actually return focus.
				// return;
			}

			// Defer to the component's own explicit focus return behavior, if
			// specified. This allows for support that the `onFocusReturn`
			// decides to allow the default behavior to occur under some
			// conditions.
			if ( onFocusReturnRef.current ) {
				onFocusReturnRef.current();
			} else {
				const focusedElement =
					focusedBeforeMount.current as HTMLElement;
				( focusedElement?.isConnected
					? focusedElement
					: ( origin as HTMLElement )
				)?.focus();
			}
			origin = null;
		}
	}, [] );
}
