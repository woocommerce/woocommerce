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

export function BlockInspector() {
	const { blockName, templateBlockId, templateBlockOrder } =
		useFocusedBlock();

	return (
		<div className="woocommerce-product-editor-dev-tools-block-inspector">
			<dl className="woocommerce-product-editor-dev-tools-block-inspector__properties">
				<dt>Block name</dt>
				<dd>{ blockName }</dd>

				<dt>Template block id</dt>
				<dd>{ templateBlockId }</dd>

				<dt>Template block order</dt>
				<dd>{ templateBlockOrder }</dd>
			</dl>
		</div>
	);
}
