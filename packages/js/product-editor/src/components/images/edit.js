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
			<h4>{ __( 'Image gallery', 'woocommerce' ) }</h4>
			<InnerBlocks
				allowedBlocks={ 'core/image' }
				template={ [ [ 'core/image', attributes ] ] }
				templateLock="all"
			/>
		</div>
	);
}
