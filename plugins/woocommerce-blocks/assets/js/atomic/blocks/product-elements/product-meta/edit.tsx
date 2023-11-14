/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { InnerBlockTemplate } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import './editor.scss';
import { useIsDescendentOfSingleProductTemplate } from '../shared/use-is-descendent-of-single-product-template';

const Edit = () => {
	const isDescendentOfSingleProductTemplate =
		useIsDescendentOfSingleProductTemplate();

	const TEMPLATE: InnerBlockTemplate[] = [
		[
			'core/group',
			{ layout: { type: 'flex', flexWrap: 'nowrap' } },
			[
				[
					'woocommerce/product-sku',
					{
						isDescendentOfSingleProductTemplate,
					},
				],
				[
					'core/post-terms',
					{
						prefix: 'Category: ',
						term: 'product_cat',
					},
				],
				[
					'core/post-terms',
					{
						prefix: 'Tags: ',
						term: 'product_tag',
					},
				],
			],
		],
	];
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InnerBlocks template={ TEMPLATE } />
		</div>
	);
};

export default Edit;
