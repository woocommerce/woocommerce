/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import { useInstanceId } from '@wordpress/compose';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ImageSizing } from '../../atomic/blocks/product-elements/image/types';
import { ProductCollectionAttributes } from './types';
import { VARIATION_NAME as PRODUCT_TITLE_ID } from './variations/elements/product-title';
import InspectorControls from './inspector-controls';

export const INNER_BLOCKS_TEMPLATE: InnerBlockTemplate[] = [
	[
		'woocommerce/product-template',
		{},
		[
			[
				'woocommerce/product-image',
				{
					imageSizing: ImageSizing.THUMBNAIL,
				},
			],
			[
				'core/post-title',
				{
					textAlign: 'center',
					level: 3,
					fontSize: 'medium',
					style: {
						spacing: {
							margin: {
								bottom: '0.75rem',
								top: '0',
							},
						},
					},
					isLink: true,
					__woocommerceNamespace: PRODUCT_TITLE_ID,
				},
			],
			[
				'woocommerce/product-price',
				{
					textAlign: 'center',
					fontSize: 'small',
				},
			],
			[
				'woocommerce/product-button',
				{
					textAlign: 'center',
					fontSize: 'small',
				},
			],
		],
	],
	[
		'core/query-pagination',
		{
			layout: {
				type: 'flex',
				justifyContent: 'center',
			},
		},
	],
	[ 'core/query-no-results' ],
];

const Edit = ( props: BlockEditProps< ProductCollectionAttributes > ) => {
	const { attributes, setAttributes } = props;
	const { queryId } = attributes;

	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: INNER_BLOCKS_TEMPLATE,
	} );

	const instanceId = useInstanceId( Edit );

	// We need this for multi-query block pagination.
	// Query parameters for each block are scoped to their ID.
	useEffect( () => {
		if ( ! Number.isFinite( queryId ) ) {
			setAttributes( { queryId: instanceId } );
		}
	}, [ queryId, instanceId, setAttributes ] );

	return (
		<div { ...blockProps }>
			<InspectorControls { ...props } />
			<div { ...innerBlocksProps } />
		</div>
	);
};

export default Edit;
