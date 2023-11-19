/**
 * Internal dependencies
 */
import { BlockTemplateArray, EvaluationContext } from '../types';
import { BlockTemplateTreeItem } from './block-template-tree-item';

export function BlockTemplateTree( {
	template,
	evaluationContext,
	onSelect,
}: {
	template: BlockTemplateArray;
	evaluationContext: EvaluationContext;
	onSelect: ( blockTemplateId: string ) => void;
} ) {
	return (
		<div className="woocommerce-product-editor-dev-tools-template-tree">
			{ template.map( ( blockTemplate, index ) => (
				<BlockTemplateTreeItem
					key={ index }
					blockTemplate={ blockTemplate }
					evaluationContext={ evaluationContext }
					onSelect={ onSelect }
				/>
			) ) }
		</div>
	);
}
