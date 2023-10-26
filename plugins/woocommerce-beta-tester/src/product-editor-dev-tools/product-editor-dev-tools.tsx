/**
 * External dependencies
 */
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { BlockInspector } from './block-inspector';
import { ProductEditorDevToolsMenu } from './product-editor-dev-tools-menu';

export function ProductEditorDevTools() {
	const [ shouldShowDevTools, setShouldShowDevTools ] =
		useState< boolean >( false );

	return (
		<>
			<ProductEditorDevToolsMenu
				shouldShowDevTools={ shouldShowDevTools }
				onToggleShowDevTools={ () => {
					setShouldShowDevTools( ! shouldShowDevTools );
				} }
			/>

			{ shouldShowDevTools && <BlockInspector /> }
		</>
	);
}
