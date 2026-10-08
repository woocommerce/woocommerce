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

const Edit = () => {
	const TEMPLATE: InnerBlockTemplate[] = [
		[
			'core/group',
			{ layout: { type: 'flex', flexWrap: 'nowrap' } },
			[
				[ 'woocommerce/product-sku' ],
				[
					'core/post-terms',
					{
						// eslint-disable-next-line @wordpress/i18n-no-flanking-whitespace
						prefix: __( 'Category: ', 'woocommerce' ),
						term: 'product_cat',
					},
				],
				[
					'core/post-terms',
					{
						// eslint-disable-next-line @wordpress/i18n-no-flanking-whitespace
						prefix: __( 'Tags: ', 'woocommerce' ),
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
