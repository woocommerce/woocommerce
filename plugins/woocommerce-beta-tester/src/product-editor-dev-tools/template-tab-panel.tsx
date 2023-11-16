/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TabPanel } from './tab-panel';

/* @ts-ignore */
function BlockTemplate( { configuration }: { configuration } ) {
	const name = configuration[ 0 ];
	const attributes = configuration[ 1 ];
	const innerBlocks = configuration[ 2 ] ?? [];

	const templateBlockId = attributes?._templateBlockId;
	const templateBlockOrder = attributes?._templateBlockOrder;

	return (
		<div className="woocommerce-product-editor-dev-tools-template-block">
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
			<div>{ JSON.stringify( attributes, null, 4 ) }</div>
			<div className="woocommerce-product-editor-dev-tools-template__inner-blocks">
				{ /* @ts-ignore */ }
				{ innerBlocks.map( ( childConfiguration, index ) => (
					<BlockTemplate
						configuration={ childConfiguration }
						key={ index }
					/>
				) ) }
			</div>
		</div>
	);
}

export function TemplateTabPanel( {
	isSelected,
	postType,
}: {
	isSelected: boolean;
	postType: string;
} ) {
	const template =
		// @ts-ignore
		globalThis.productBlockEditorSettings.templates[ postType ];

	return (
		<TabPanel isSelected={ isSelected }>
			<div className="woocommerce-product-editor-dev-tools-template">
				{ /* @ts-ignore */ }
				{ template.map( ( blockConfiguration, index ) => (
					<BlockTemplate
						configuration={ blockConfiguration }
						key={ index }
					/>
				) ) }

				{ JSON.stringify( template, null, 4 ) }
			</div>
		</TabPanel>
	);
}
