/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { TabPanel } from './tab-panel';

export function BlockInspectorTabPanel( {
	isSelected,
	selectedBlock,
}: {
	isSelected: boolean;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	selectedBlock: any;
} ) {
	return (
		<TabPanel isSelected={ isSelected }>
			<div className="woocommerce-product-editor-dev-tools-block-inspector">
				{ ! selectedBlock && (
					<p>
						{ __(
							'Focus on a block to see its details.',
							'woocommerce'
						) }
					</p>
				) }

				{ selectedBlock && (
					<div>{ JSON.stringify( selectedBlock, null, 4 ) }</div>
				) }
			</div>
		</TabPanel>
	);
}
