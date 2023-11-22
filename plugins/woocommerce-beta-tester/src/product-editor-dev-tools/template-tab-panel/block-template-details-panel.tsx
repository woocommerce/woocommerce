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
		...regularAttributesFromTemplate
	} = attributesFromTemplate;

	const clientId = block?.clientId;
	const attributesFromBlock = block?.attributes;

	const {
		_templateBlockId: blockTemplateId = undefined,
		_templateBlockOrder: blockTemplateOrder = undefined,
		...regularAttributesFromBlock
	} = attributesFromBlock ?? {};

	const regularAttributes = {
		...regularAttributesFromTemplate,
		...regularAttributesFromBlock,
	};

	return (
		<div className="woocommerce-product-editor-dev-tools-block-template-details">
			<div>{ templateBlockId }</div>
			<div>
				<span>{ name }</span>
				<span>{ templateBlockOrder }</span>
				<span>{ clientId ?? __( 'unknown', 'woocommerce' ) }</span>
			</div>

			<dl className="woocommerce-product-editor-dev-tools-block-template-details__attributes">
				{ Object.entries( regularAttributes ).map(
					( [ attributeName, attributeValue ] ) => (
						<>
							<dt className="woocommerce-product-editor-dev-tools-block-template-details__attribute-name">
								{ attributeName }
							</dt>
							<dd className="woocommerce-product-editor-dev-tools-block-template-details__attribute-value">
								{ JSON.stringify( attributeValue ) }
							</dd>
						</>
					)
				) }
			</dl>
		</div>
	);
}
