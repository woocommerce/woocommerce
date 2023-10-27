/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

export function BlockInspector( {
	blockInfo: { blockName, templateBlockId, templateBlockOrder },
}: {
	blockInfo: {
		blockName?: string | null;
		templateBlockId?: string | null;
		templateBlockOrder?: number | null;
	};
} ) {
	return (
		<div className="woocommerce-product-editor-dev-tools-block-inspector">
			{ ! blockName && (
				<p>
					{ __(
						'Focus on a block to see its details.',
						'woocommerce'
					) }
				</p>
			) }

			{ blockName && (
				<dl className="woocommerce-product-editor-dev-tools-block-inspector__properties">
					<dt>Block name</dt>
					<dd>{ blockName }</dd>

					<dt>Template block id</dt>
					<dd>{ templateBlockId }</dd>

					<dt>Template block order</dt>
					<dd>{ templateBlockOrder }</dd>
				</dl>
			) }
		</div>
	);
}
