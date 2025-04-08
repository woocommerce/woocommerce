/**
 * External dependencies
 */
import { useState } from 'react';

/**
 * Internal dependencies
 */
import { ProductEditorDevToolsBar } from './product-editor-dev-tools-bar';
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

			{ shouldShowDevTools && (
				<ProductEditorDevToolsBar
					onClose={ () => setShouldShowDevTools( false ) }
				/>
			) }
		</>
	);
}
