/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export function Edit( { attributes } ) {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<h4>{ __( 'Summary', 'woocommerce' ) }</h4>
			<InnerBlocks
				allowedBlocks={ [ 'core/paragraph' ] }
				template={ [ [ 'core/paragraph', attributes ] ] }
				templateLock="all"
			/>
		</div>
	);
}
