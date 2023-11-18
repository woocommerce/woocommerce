/**
 * Internal dependencies
 */
import { EvaluationContext } from './types';
import { ExpressionsPanel } from './expressions-panel';
import { TabPanel } from './tab-panel';

export function ProductTabPanel( {
	isSelected,
	evaluationContext,
}: {
	isSelected: boolean;
	evaluationContext: EvaluationContext;
} ) {
	return (
		<TabPanel isSelected={ isSelected }>
			<div className="woocommerce-product-editor-dev-tools-product">
				<div className="woocommerce-product-editor-dev-tools-product-entity">
					{ JSON.stringify(
						evaluationContext.editedProduct,
						null,
						4
					) }
				</div>
				<ExpressionsPanel evaluationContext={ evaluationContext } />
			</div>
		</TabPanel>
	);
}
