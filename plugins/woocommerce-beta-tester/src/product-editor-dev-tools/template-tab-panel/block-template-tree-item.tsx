/**
 * Internal dependencies
 */
import { BlockTemplate } from './types';

export function BlockTemplateTreeItem( {
	blockTemplate,
	onSelect,
}: {
	blockTemplate: BlockTemplate;
	onSelect: ( blockTemplateId: string ) => void;
} ) {
	const name = blockTemplate[ 0 ];
	const attributes = blockTemplate[ 1 ];
	const innerBlocks = blockTemplate[ 2 ];

	const templateBlockId = attributes?._templateBlockId;
	const templateBlockOrder = attributes?._templateBlockOrder;

	function onClick( event: React.MouseEvent ) {
		event.stopPropagation();

		onSelect( templateBlockId );
	}

	return (
		<div
			className="woocommerce-product-editor-dev-tools-template-block"
			onClick={ onClick }
		>
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
			{ innerBlocks && (
				<div className="woocommerce-product-editor-dev-tools-template__inner-blocks">
					{ innerBlocks.map( ( innerBlockTemplate, index ) => (
						<BlockTemplateTreeItem
							blockTemplate={ innerBlockTemplate }
							key={ index }
							onSelect={ onSelect }
						/>
					) ) }
				</div>
			) }
		</div>
	);
}
