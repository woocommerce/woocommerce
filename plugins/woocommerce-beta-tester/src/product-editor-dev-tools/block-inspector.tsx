/**
 * External dependencies
 */
import { useEffect, useState } from 'react';

function useFocusedBlock() {
	const [ focusedElement, setFocusedElement ] = useState< Element | null >();
	useEffect( () => {
		function handleFocus( event: FocusEvent ) {
			const target = event.target;

			if ( ! ( target instanceof Element ) ) {
				return;
			}

			setFocusedElement( target.closest( '[data-block]' ) );
		}

		document.addEventListener( 'focus', handleFocus, {
			capture: true,
		} );

		return () => {
			document.removeEventListener( 'focus', handleFocus, {
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

export function BlockInspector() {
	const { blockName, templateBlockId, templateBlockOrder } =
		useFocusedBlock();

	return (
		<div>
			<div>Block name: { blockName }</div>
			<div>Template block id: { templateBlockId }</div>
			<div>Template block order: { templateBlockOrder }</div>
		</div>
	);
}
