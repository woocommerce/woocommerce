/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BlockTemplate } from '../types';
import { ExpressionField } from '../expression-field';

export function BlockTemplateDetailsPanel( {
	blockTemplate,
	block,
	evaluationContext,
}: {
	blockTemplate?: BlockTemplate | null;
	block: any;
	evaluationContext: {
		postType: string;
		editedProduct: Product;
	};
} ) {
	if ( ! blockTemplate ) {
		return null;
	}

	const name = blockTemplate[ 0 ];
	const attributesFromTemplate = blockTemplate[ 1 ];

	const {
		_templateBlockId: templateBlockId,
		_templateBlockOrder: templateBlockOrder,
		_templateBlockDisableConditions:
			templateBlockDisableConditionsFromTemplate,
		_templateBlockHideConditions: templateBlockHideConditionsFromTemplate,
		...regularAttributesFromTemplate
	} = attributesFromTemplate;

	const clientId = block?.clientId;
	const attributesFromBlock = block?.attributes;

	const {
		_templateBlockId: blockTemplateId = undefined,
		_templateBlockOrder: blockTemplateOrder = undefined,
		_templateBlockDisableConditions:
			templateBlockDisableConditionsFromBlock = undefined,
		_templateBlockHideConditions:
			templateBlockHideConditionsFromBlock = undefined,
		...regularAttributesFromBlock
	} = attributesFromBlock ?? {};

	const regularAttributes = {
		...regularAttributesFromTemplate,
		...regularAttributesFromBlock,
	};

	const templateBlockDisableConditions =
		templateBlockDisableConditionsFromBlock ??
		templateBlockDisableConditionsFromTemplate;

	const templateBlockHideConditions =
		templateBlockHideConditionsFromBlock ??
		templateBlockHideConditionsFromTemplate;

	return (
		<div className="woocommerce-product-editor-dev-tools-block-template-details">
			<div>{ templateBlockId }</div>
			<div>
				<span>{ name }</span>
				<span>{ templateBlockOrder }</span>
			</div>

			<div>{ clientId ?? __( 'unknown', 'woocommerce' ) }</div>

			{ templateBlockDisableConditions && (
				<div>
					<div>Disable conditions:</div>
					<ul>
						{ templateBlockDisableConditions.map(
							// @ts-ignore
							( condition, index ) => (
								<li key={ index }>
									<ExpressionField
										expression={ condition.expression }
										evaluationContext={ evaluationContext }
										mode="view"
									/>
								</li>
							)
						) }
					</ul>
				</div>
			) }

			{ templateBlockHideConditions && (
				<div>
					<div>Hide conditions:</div>
					<ul>
						{ templateBlockHideConditions.map(
							// @ts-ignore
							( condition, index ) => (
								<li key={ index }>
									<ExpressionField
										expression={ condition.expression }
										evaluationContext={ evaluationContext }
										mode="view"
									/>
								</li>
							)
						) }
					</ul>
				</div>
			) }

			<dl className="woocommerce-product-editor-dev-tools-block-template-details__attributes">
				{ Object.entries( regularAttributes ).map(
					( [ attributeName, attributeValue ] ) => (
						<>
							<dt className="woocommerce-product-editor-dev-tools-block-template-details__attribute-name">
								{ attributeName }
							</dt>
							<dd className="woocommerce-product-editor-dev-tools-block-template-details__attribute-value">
								{ JSON.stringify( attributeValue, null, 4 ) }
							</dd>
						</>
					)
				) }
			</dl>
		</div>
	);
}
