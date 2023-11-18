/**
 * Internal dependencies
 */
import { BlockTemplate } from '../types';

export function BlockTemplateDetailsPanel( {
	blockTemplate,
}: {
	blockTemplate?: BlockTemplate | null;
} ) {
	if ( ! blockTemplate ) {
		return null;
	}

	const name = blockTemplate[ 0 ];
	const attributes = blockTemplate[ 1 ];

	const {
		_templateBlockId: templateBlockId,
		_templateBlockOrder: templateBlockOrder,
		...regularAttributes
	} = attributes;

	return (
		<div className="woocommerce-product-editor-dev-tools-block-template-details">
			<div>{ templateBlockId }</div>
			<div>
				<span>{ name }</span>
				<span>{ templateBlockOrder }</span>
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
