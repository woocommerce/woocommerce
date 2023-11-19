/**
 * Internal dependencies
 */
import { BlockTemplateArray, EvaluationContext } from '../types';
import { BlockTemplateTreeItem } from './block-template-tree-item';

export function BlockTemplateTree( {
	template,
	evaluationContext,
	selectedBlockTemplateId,
	onSelect,
}: {
	template: BlockTemplateArray;
	evaluationContext: EvaluationContext;
	selectedBlockTemplateId: string | null;
	onSelect: ( blockTemplateId: string ) => void;
} ) {
	return (
		<div className="woocommerce-product-editor-dev-tools-template-tree">
			{ template.map( ( blockTemplate, index ) => (
				<BlockTemplateTreeItem
					key={ index }
					blockTemplate={ blockTemplate }
					evaluationContext={ evaluationContext }
					selectedBlockTemplateId={ selectedBlockTemplateId }
					onSelect={ onSelect }
				/>
			) ) }
		</div>
	);
}
