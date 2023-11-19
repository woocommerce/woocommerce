/**
 * External dependencies
 */
import { evaluate } from '@woocommerce/expression-evaluation';

/**
 * Internal dependencies
 */
import { BlockTemplate, EvaluationContext } from '../types';

export function BlockTemplateTreeItem( {
	blockTemplate,
	evaluationContext,
	onSelect,
}: {
	blockTemplate: BlockTemplate;
	evaluationContext: EvaluationContext;
	onSelect: ( blockTemplateId: string ) => void;
} ) {
	const name = blockTemplate[ 0 ];
	const attributes = blockTemplate[ 1 ];
	const innerBlocks = blockTemplate[ 2 ];

	const templateBlockId = attributes?._templateBlockId;
	const templateBlockOrder = attributes?._templateBlockOrder;

	const templateBlockHideConditions =
		attributes?._templateBlockHideConditions;
	const templateBlockDisableConditions =
		attributes?._templateBlockDisableConditions;

	const isConditionallyHidden =
		templateBlockHideConditions &&
		templateBlockHideConditions.some( ( condition ) =>
			evaluate( condition.expression, evaluationContext )
		);
	const isConditionallyDisabled =
		templateBlockDisableConditions &&
		templateBlockDisableConditions.some( ( condition ) =>
			evaluate( condition.expression, evaluationContext )
		);

	function onClick( event: React.MouseEvent ) {
		event.stopPropagation();

		onSelect( templateBlockId );
	}

	return (
		<div
			className="woocommerce-product-editor-dev-tools-template-block"
			onClick={ onClick }
		>
			<div className="woocommerce-product-editor-dev-tools-template-block__row">
				<div>
					<div className="woocommerce-product-editor-dev-tools-template-block__header">
						{ templateBlockId }
					</div>
					<div className="woocommerce-product-editor-dev-tools-template-block__sub-header">
						<span className="woocommerce-product-editor-dev-tools-template-block__name">
							{ name }
						</span>
						<span className="woocommerce-product-editor-dev-tools-template-block__order">
							{ templateBlockOrder }
						</span>
					</div>
				</div>
				<div>
					<span>
						hidden: { JSON.stringify( isConditionallyHidden ) }
					</span>
					<span>
						disabled: { JSON.stringify( isConditionallyDisabled ) }
					</span>
				</div>
			</div>

			{ innerBlocks && (
				<div className="woocommerce-product-editor-dev-tools-template__inner-blocks">
					{ innerBlocks.map( ( innerBlockTemplate, index ) => (
						<BlockTemplateTreeItem
							blockTemplate={ innerBlockTemplate }
							evaluationContext={ evaluationContext }
							key={ index }
							onSelect={ onSelect }
						/>
					) ) }
				</div>
			) }
		</div>
	);
}
