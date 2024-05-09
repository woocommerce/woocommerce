/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Warning from './components/warning';
import './editor.scss';
import { getAllowedBlocks } from './utils';
import { BLOCK_NAME_MAP } from './constants';
import type { FilterType } from './types';

const Edit = ( {
	attributes,
	clientId,
}: BlockEditProps< {
	heading: string;
	filterType: FilterType;
	isPreview: boolean;
	attributeId: number | undefined;
} > ) => {
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
			<InnerBlocks
				allowedBlocks={ getAllowedBlocks( [
					...Object.values( BLOCK_NAME_MAP ),
					'woocommerce/product-filter',
					'woocommerce/filter-wrapper',
					'woocommerce/product-collection',
					'core/query',
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
							isPreview: attributes.isPreview,
							attributeId:
								attributes.filterType === 'attribute-filter' &&
								attributes.attributeId
									? attributes.attributeId
									: undefined,
						},
					],
				] }
			/>
		</nav>
	);
};

export default Edit;
