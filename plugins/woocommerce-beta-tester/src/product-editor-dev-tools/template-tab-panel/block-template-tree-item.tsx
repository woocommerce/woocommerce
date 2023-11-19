/**
 * External dependencies
 */
import { evaluate } from '@woocommerce/expression-evaluation';
import { Icon, blockDefault, warning } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { BlockTemplate, EvaluationContext } from '../types';

export function BlockTemplateTreeItem( {
	blockTemplate,
	evaluationContext,
	selectedBlockTemplateId,
	onSelect,
}: {
	blockTemplate: BlockTemplate;
	evaluationContext: EvaluationContext;
	selectedBlockTemplateId: string | null;
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

	const isSelected = selectedBlockTemplateId === templateBlockId;

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
			className={ 'woocommerce-product-editor-dev-tools-template-block' }
			onClick={ onClick }
		>
			<div
				className={
					'woocommerce-product-editor-dev-tools-template-block__row ' +
					`${ isSelected ? 'selected' : '' }` +
					`${
						isConditionallyHidden ? 'conditionally-hidden' : ''
					} ` +
					`${
						isConditionallyDisabled ? 'conditionally-disabled' : ''
					}`
				}
			>
				<Icon
					icon={ blockDefault }
					className="woocommerce-product-editor-dev-tools-template-block__row__icon"
				/>
				<div>
					<div className="woocommerce-product-editor-dev-tools-template-block__row__header">
						{ templateBlockId }
					</div>
					<div className="woocommerce-product-editor-dev-tools-template-block__row__sub-header">
						<span className="woocommerce-product-editor-dev-tools-template-block__name">
							{ name }
						</span>
						<span className="woocommerce-product-editor-dev-tools-template-block__order">
							{ templateBlockOrder }
						</span>
					</div>
				</div>
				<div className="woocommerce-product-editor-dev-tools-template-block__indicators">
					{ templateBlockHideConditions && (
						<Icon
							icon={ warning }
							className="woocommerce-product-editor-dev-tools-template-block__conditionally-hidden-indicator"
						/>
					) }
					{ templateBlockDisableConditions && (
						<Icon
							icon={ warning }
							className="woocommerce-product-editor-dev-tools-template-block__conditionally-disabled-indicator"
						/>
					) }
				</div>
			</div>

			{ innerBlocks && (
				<div className="woocommerce-product-editor-dev-tools-template__inner-blocks">
					{ innerBlocks.map( ( innerBlockTemplate, index ) => (
						<BlockTemplateTreeItem
							blockTemplate={ innerBlockTemplate }
							evaluationContext={ evaluationContext }
							key={ index }
							selectedBlockTemplateId={ selectedBlockTemplateId }
							onSelect={ onSelect }
						/>
					) ) }
				</div>
			) }
		</div>
	);
}
