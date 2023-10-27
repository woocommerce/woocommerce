/**
 * External dependencies
 */
import { useEffect, useState } from 'react';

export function useFocusedBlock() {
	const [ focusedElement, setFocusedElement ] = useState< Element | null >();
	useEffect( () => {
		function handleFocus( event: FocusEvent ) {
			const target = event.target;

			if ( ! ( target instanceof Element ) ) {
				return;
			}

			setFocusedElement( target.closest( '[data-block]' ) );
		}

		const productEditorWrapper = document.querySelector(
			'.woocommerce-product-block-editor'
		) as HTMLElement | null;

		productEditorWrapper?.addEventListener( 'focus', handleFocus, {
			capture: true,
		} );

		return () => {
			productEditorWrapper?.removeEventListener( 'focus', handleFocus, {
				capture: true,
			} );
		};
	}, [] );

	const blockInfo = {
		blockName: focusedElement?.getAttribute( 'data-type' ),
		templateBlockId: focusedElement?.getAttribute(
			'data-template-block-id'
		),
		templateBlockOrder: focusedElement?.getAttribute(
			'data-template-block-order'
		),
	};

	return blockInfo;
}
