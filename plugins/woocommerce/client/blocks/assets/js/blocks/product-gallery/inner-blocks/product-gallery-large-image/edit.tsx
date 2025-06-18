/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export const Edit = () => {
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'wc-block-product-gallery-large-image__inner-blocks',
		},
		{
			templateInsertUpdatesSelection: true,
		}
	);

	const blockProps = useBlockProps( {
		className:
			'wc-block-product-gallery-large-image wc-block-editor-product-gallery-large-image',
	} );

	return (
		<div { ...blockProps }>
			<div { ...innerBlocksProps } />
		</div>
	);
};
