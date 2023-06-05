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
import type {
	ProductCollectionAttributes,
	ProductCollectionQuery,
} from './types';
import { VARIATION_NAME as PRODUCT_TITLE_ID } from './variations/elements/product-title';
import InspectorControls from './inspector-controls';
import { DEFAULT_ATTRIBUTES } from './constants';
import './editor.scss';
import { getDefaultValueOfInheritQueryFromTemplate } from './utils';

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
			setAttributes( { queryId: Number( instanceId ) } );
		}
	}, [ queryId, instanceId, setAttributes ] );

	/**
	 * Because of issue https://github.com/WordPress/gutenberg/issues/7342,
	 * We are using this workaround to set default attributes.
	 */
	useEffect( () => {
		setAttributes( {
			...DEFAULT_ATTRIBUTES,
			query: {
				...( DEFAULT_ATTRIBUTES.query as ProductCollectionQuery ),
				inherit: getDefaultValueOfInheritQueryFromTemplate(),
			},
			...( attributes as Partial< ProductCollectionAttributes > ),
		} );
		// We don't wanna add attributes as a dependency here.
		// Because we want this to run only once.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ setAttributes ] );

	/**
	 * If inherit is not a boolean, then we haven't set default attributes yet.
	 * We don't wanna render anything until default attributes are set.
	 * Default attributes are set in the useEffect above.
	 */
	if ( typeof attributes?.query?.inherit !== 'boolean' ) return null;

	return (
		<div { ...blockProps }>
			<InspectorControls { ...props } />
			<div { ...innerBlocksProps } />
		</div>
	);
};

export default Edit;
