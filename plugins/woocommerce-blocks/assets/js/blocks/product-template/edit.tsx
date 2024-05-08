/* eslint-disable @typescript-eslint/naming-convention */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { memo, useMemo, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import {
	BlockContextProvider,
	__experimentalUseBlockPreview as useBlockPreview,
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { ProductCollectionAttributes } from '@woocommerce/blocks/product-collection/types';
import { getSettingWithCoercion } from '@woocommerce/settings';
import { isNumber, ProductResponseItem } from '@woocommerce/types';
import { ProductDataContextProvider } from '@woocommerce/shared-context';
import { withProduct } from '@woocommerce/block-hocs';
import type { BlockEditProps, BlockInstance } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { useGetLocation, useProductCollectionQueryContext } from './utils';
import './editor.scss';

const DEFAULT_QUERY_CONTEXT_ATTRIBUTES = [ 'collection' ];

const ProductTemplateInnerBlocks = () => {
	const innerBlocksProps = useInnerBlocksProps(
		{ className: 'wc-block-product' },
		{ __unstableDisableLayoutClassNames: true }
	);
	return <li { ...innerBlocksProps } />;
};

type ProductTemplateBlockPreviewProps = {
	blocks: object[];
	blockContextId: string;
	isHidden: boolean;
	setActiveBlockContextId: ( blockContextId: string ) => void;
};

const ProductTemplateBlockPreview = ( {
	blocks,
	blockContextId,
	isHidden,
	setActiveBlockContextId,
}: ProductTemplateBlockPreviewProps ) => {
	const blockPreviewProps = useBlockPreview( {
		blocks,
		props: {
			className: 'wc-block-product',
		},
	} );

	const handleOnClick = () => {
		setActiveBlockContextId( blockContextId );
	};

	const style = {
		display: isHidden ? 'none' : undefined,
	};

	return (
		<li
			{ ...blockPreviewProps }
			tabIndex={ 0 }
			// eslint-disable-next-line jsx-a11y/no-noninteractive-element-to-interactive-role
			role="button"
			onClick={ handleOnClick }
			onKeyPress={ handleOnClick }
			style={ style }
		/>
	);
};

const MemoizedProductTemplateBlockPreview = memo( ProductTemplateBlockPreview );

type ProductContentProps = {
	attributes: { productId: string };
	isLoading: boolean;
	product: ProductResponseItem;
	displayTemplate: boolean;
	blocks: BlockInstance[];
	blockContext: {
		postType: string;
		postId: string;
	};
	setActiveBlockContextId: ( id: string ) => void;
};

const ProductContent = withProduct(
	( {
		isLoading,
		product,
		displayTemplate,
		blocks,
		blockContext,
		setActiveBlockContextId,
	}: ProductContentProps ) => {
		return (
			<BlockContextProvider
				key={ blockContext.postId }
				value={ blockContext }
			>
				<ProductDataContextProvider
					product={ product }
					isLoading={ isLoading }
				>
					{ displayTemplate ? <ProductTemplateInnerBlocks /> : null }
					<MemoizedProductTemplateBlockPreview
						blocks={ blocks }
						blockContextId={ blockContext.postId }
						setActiveBlockContextId={ setActiveBlockContextId }
						isHidden={ displayTemplate }
					/>
				</ProductDataContextProvider>
			</BlockContextProvider>
		);
	}
);

const ProductTemplateEdit = (
	props: BlockEditProps< {
		clientId: string;
	} > & {
		context: ProductCollectionAttributes;
		__unstableLayoutClassNames: string;
	}
) => {
	const {
		clientId,
		context: {
			query: {
				perPage,
				offset = 0,
				order,
				orderBy,
				search,
				exclude,
				inherit,
				taxQuery,
				pages,
				...restQueryArgs
			},
			queryContext = [ { page: 1 } ],
			templateSlug,
			displayLayout: { type: layoutType, columns, shrinkColumns } = {
				type: 'flex',
				columns: 3,
				shrinkColumns: false,
			},
			queryContextIncludes = [],
			__privateProductCollectionPreviewState,
		},
		__unstableLayoutClassNames,
	} = props;
	const location = useGetLocation( props.context, props.clientId );

	const [ { page } ] = queryContext;
	const [ activeBlockContextId, setActiveBlockContextId ] =
		useState< string >();
	const postType = 'product';
	const loopShopPerPage = getSettingWithCoercion(
		'loopShopPerPage',
		12,
		isNumber
	);

	// Add default query context attributes to queryContextIncludes
	const queryContextIncludesWithDefaults = [
		...new Set(
			queryContextIncludes.concat( DEFAULT_QUERY_CONTEXT_ATTRIBUTES )
		),
	];

	const productCollectionQueryContext = useProductCollectionQueryContext( {
		clientId,
		queryContextIncludes: queryContextIncludesWithDefaults,
	} );

	const { products, blocks } = useSelect(
		( select ) => {
			const { getEntityRecords, getTaxonomies } = select( coreStore );
			const { getBlocks } = select( blockEditorStore );
			const taxonomies = getTaxonomies( {
				type: postType,
				per_page: -1,
				context: 'view',
			} );
			const templateCategory =
				inherit &&
				templateSlug?.startsWith( 'category-' ) &&
				getEntityRecords( 'taxonomy', 'category', {
					context: 'view',
					per_page: 1,
					_fields: [ 'id' ],
					slug: templateSlug.replace( 'category-', '' ),
				} );
			const query: Record< string, unknown > = {
				postType,
				offset: perPage ? perPage * ( page - 1 ) + offset : 0,
				order,
				orderby: orderBy,
			};
			// There is no need to build the taxQuery if we inherit.
			if ( taxQuery && ! inherit ) {
				// We have to build the tax query for the REST API and use as
				// keys the taxonomies `rest_base` with the `term ids` as values.
				const builtTaxQuery = Object.entries( taxQuery ).reduce(
					( accumulator, [ taxonomySlug, terms ] ) => {
						const taxonomy = taxonomies?.find(
							( { slug } ) => slug === taxonomySlug
						);
						if ( taxonomy?.rest_base ) {
							accumulator[ taxonomy?.rest_base ] = terms;
						}
						return accumulator;
					},
					{}
				);
				if ( !! Object.keys( builtTaxQuery ).length ) {
					Object.assign( query, builtTaxQuery );
				}
			}
			if ( perPage ) {
				query.per_page = perPage;
			}
			if ( search ) {
				query.search = search;
			}
			if ( exclude?.length ) {
				query.exclude = exclude;
			}
			// If `inherit` is truthy, adjust conditionally the query to create a better preview.
			if ( inherit ) {
				if ( templateCategory ) {
					query.categories = templateCategory[ 0 ]?.id;
				}
				query.per_page = loopShopPerPage;
			}
			return {
				products: getEntityRecords( 'postType', postType, {
					...query,
					...restQueryArgs,
					location,
					productCollectionQueryContext,
					previewState: __privateProductCollectionPreviewState,
				} ),
				blocks: getBlocks( clientId ),
			};
		},
		[
			perPage,
			page,
			offset,
			order,
			orderBy,
			clientId,
			search,
			postType,
			exclude,
			inherit,
			templateSlug,
			taxQuery,
			restQueryArgs,
			location,
			productCollectionQueryContext,
			loopShopPerPage,
			__privateProductCollectionPreviewState,
		]
	);
	const blockContexts = useMemo(
		() =>
			products?.map( ( product ) => ( {
				postType: product.type,
				postId: product.id,
			} ) ),
		[ products ]
	);

	const hasLayoutFlex = layoutType === 'flex' && columns > 1;
	let customClassName = '';
	if ( hasLayoutFlex ) {
		const dynamicGrid = `wc-block-product-template__responsive columns-${ columns }`;
		const staticGrid = `is-flex-container columns-${ columns }`;

		customClassName = shrinkColumns ? dynamicGrid : staticGrid;
	}

	const blockProps = useBlockProps( {
		className: classnames(
			__unstableLayoutClassNames,
			'wc-block-product-template',
			customClassName
		),
	} );

	if ( ! products ) {
		return (
			<p { ...blockProps }>
				<Spinner className="wc-block-product-template__spinner" />
			</p>
		);
	}

	if ( ! products.length ) {
		return (
			<p { ...blockProps }>
				{ ' ' }
				{ __( 'No results found.', 'woocommerce' ) }
			</p>
		);
	}

	// To avoid flicker when switching active block contexts, a preview is rendered
	// for each block context, but the preview for the active block context is hidden.
	// This ensures that when it is displayed again, the cached rendering of the
	// block preview is used, instead of having to re-render the preview from scratch.
	return (
		<ul { ...blockProps }>
			{ blockContexts &&
				blockContexts.map( ( blockContext ) => {
					const displayTemplate =
						blockContext.postId ===
						( activeBlockContextId || blockContexts[ 0 ]?.postId );

					return (
						// eslint-disable-next-line @typescript-eslint/ban-ts-comment
						// @ts-ignore isLoading and product props are missing as they're coming from untyped withProduct HOC.
						<ProductContent
							key={ blockContext.postId }
							attributes={ {
								productId: blockContext.postId,
							} }
							blocks={ blocks }
							displayTemplate={ displayTemplate }
							blockContext={ blockContext }
							setActiveBlockContextId={ setActiveBlockContextId }
						/>
					);
				} ) }
		</ul>
	);
};

export default ProductTemplateEdit;
