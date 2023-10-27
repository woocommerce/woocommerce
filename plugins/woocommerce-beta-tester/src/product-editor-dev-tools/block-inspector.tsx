/**
 * Internal dependencies
 */
import { useFocusedBlock } from './hooks/use-focused-block';

export function BlockInspector() {
	const { blockName, templateBlockId, templateBlockOrder } =
		useFocusedBlock();

	return (
		<div className="woocommerce-product-editor-dev-tools-block-inspector">
			<dl className="woocommerce-product-editor-dev-tools-block-inspector__properties">
				<dt>Block name</dt>
				<dd>{ blockName }</dd>

				<dt>Template block id</dt>
				<dd>{ templateBlockId }</dd>

				<dt>Template block order</dt>
				<dd>{ templateBlockOrder }</dd>
			</dl>
		</div>
	);
}
