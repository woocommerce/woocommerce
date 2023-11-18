/**
 * Internal dependencies
 */
import { BlockTemplateArray } from './types';
import { BlockTemplateTreeItem } from './block-template-tree-item';

export function BlockTemplateTree( {
	template,
	onSelect,
}: {
	template: BlockTemplateArray;
	onSelect: ( blockTemplateId: string ) => void;
} ) {
	return (
		<div className="woocommerce-product-editor-dev-tools-template-tree">
			{ template.map( ( blockTemplate, index ) => (
				<BlockTemplateTreeItem
					blockTemplate={ blockTemplate }
					key={ index }
					onSelect={ onSelect }
				/>
			) ) }
		</div>
	);
}
