/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { BlockTemplate } from '../types';

export function BlockTemplateDetailsPanel( {
	blockTemplate,
	block,
}: {
	blockTemplate?: BlockTemplate | null;
	block: any;
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
					<div>
						{ JSON.stringify(
							templateBlockDisableConditions,
							null,
							4
						) }
					</div>
				</div>
			) }

			{ templateBlockHideConditions && (
				<div>
					<div>Hide conditions:</div>
					<div>
						{ JSON.stringify(
							templateBlockHideConditions,
							null,
							4
						) }
					</div>
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
