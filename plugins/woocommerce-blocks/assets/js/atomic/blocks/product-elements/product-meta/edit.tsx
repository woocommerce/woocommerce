/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { InnerBlockTemplate } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

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
						prefix: __(
							'Category: ',
							'woo-gutenberg-products-block'
						),
						term: 'product_cat',
					},
				],
				[
					'core/post-terms',
					{
						prefix: __( 'Tags: ', 'woo-gutenberg-products-block' ),
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
