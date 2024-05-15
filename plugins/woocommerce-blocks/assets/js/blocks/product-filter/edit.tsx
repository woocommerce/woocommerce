/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

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
					/**
					 * We want to hide the clear filter button for active filters block
					 * as it has its own "clear all" button.
					 */
					attributes.filterType === 'active-filters'
						? [
								'core/heading',
								{ level: 3, content: attributes.heading || '' },
						  ]
						: [
								'core/group',
								{
									layout: {
										type: 'flex',
										flexWrap: 'nowrap',
									},
									metadata: {
										name: __( 'Header', 'woocommerce' ),
									},
									style: {
										spacing: {
											blockGap: '0',
										},
									},
								},
								[
									[
										'core/heading',
										{
											level: 3,
											content: attributes.heading || '',
										},
									],
									[
										'woocommerce/product-filter-clear-button',
										{
											lock: {
												remove: true,
												move: false,
											},
										},
									],
								],
						  ],
					[
						BLOCK_NAME_MAP[ attributes.filterType ],
						{
							lock: {
								remove: true,
							},
							isPreview: attributes.isPreview,
						},
					],
				] }
			/>
		</nav>
	);
};

export default Edit;
