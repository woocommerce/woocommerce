/**
 * Internal dependencies
 */
import { BlockInspector } from './block-inspector';
import { ProductEditorDevToolsMenu } from './product-editor-dev-tools-menu';

export function ProductEditorDevTools() {
	return (
		<>
			<ProductEditorDevToolsMenu />

			<BlockInspector />
		</>
	);
}
