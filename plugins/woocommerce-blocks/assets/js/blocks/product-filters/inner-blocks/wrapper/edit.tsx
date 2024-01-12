/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';

const BLOCK_NAME_MAP = {
	'active-filters': 'woocommerce/product-filters-active',
	'price-filter': 'woocommerce/product-filters-price',
	'stock-filter': 'woocommerce/product-filters-stock-status',
	'rating-filter': 'woocommerce/product-filters-rating',
	'attribute-filter': 'woocommerce/product-filters-attribute',
};

type FilterType = keyof typeof BLOCK_NAME_MAP;

const Edit = ( {
	attributes,
}: BlockEditProps< { heading: string; filterType: FilterType } > ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InnerBlocks
				allowedBlocks={ [ 'core/heading' ] }
				template={ [
					[
						'core/heading',
						{ level: 3, content: attributes.heading || '' },
					],
					[
						`woocommerce/${
							BLOCK_NAME_MAP[ attributes.filterType ]
						}`,
						{
							heading: '',
							lock: {
								remove: true,
							},
						},
					],
				] }
			/>
		</div>
	);
};

export default Edit;
