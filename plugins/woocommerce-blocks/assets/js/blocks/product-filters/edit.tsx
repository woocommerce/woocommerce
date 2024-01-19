/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Downgrade from './components/downgrade';
import Warning from './components/warning';
import './editor.scss';
import { getAllowedBlocks } from './utils';

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
	clientId,
}: BlockEditProps< { heading: string; filterType: FilterType } > ) => {
	const blockProps = useBlockProps();

	const isNested = useSelect( ( select ) => {
		const { getBlockParentsByBlockName } = select( 'core/block-editor' );
		return !! getBlockParentsByBlockName(
			clientId,
			'woocommerce/product-collection'
		).length;
	} );

	return (
		<nav { ...blockProps }>
			{ ! isNested && <Warning /> }
			<Downgrade
				filterType={ attributes.filterType }
				clientId={ clientId }
			/>
			<InnerBlocks
				allowedBlocks={ getAllowedBlocks( [
					...Object.values( BLOCK_NAME_MAP ),
					'woocommerce/rating-filter',
					'woocommerce/active-filters',
					'woocommerce/attribute-filter',
					'woocommerce/price-filter',
					'woocommerce/stock-filter',
				] ) }
				template={ [
					[
						'core/heading',
						{ level: 3, content: attributes.heading || '' },
					],
					[
						BLOCK_NAME_MAP[ attributes.filterType ],
						{
							lock: {
								remove: true,
							},
						},
					],
				] }
			/>
		</nav>
	);
};

export default Edit;
