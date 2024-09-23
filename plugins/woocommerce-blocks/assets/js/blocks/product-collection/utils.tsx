/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { addFilter } from '@wordpress/hooks';
import { select, useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { isWpVersion } from '@woocommerce/settings';
import type { BlockEditProps, Block } from '@wordpress/blocks';
import {
	useEffect,
	useLayoutEffect,
	useState,
	useMemo,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { ProductResponseItem } from '@woocommerce/types';
import { getProduct } from '@woocommerce/editor-components/utils';
import {
	createBlock,
	// @ts-expect-error Type definitions for this function are missing in Guteberg
	createBlocksFromInnerBlocksTemplate,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	ProductCollectionAttributes,
	TProductCollectionOrder,
	TProductCollectionOrderBy,
	ProductCollectionQuery,
	ProductCollectionDisplayLayout,
	PreviewState,
	SetPreviewState,
	ProductCollectionUIStatesInEditor,
} from './types';
import {
	coreQueryPaginationBlockName,
	DEFAULT_QUERY,
	DEFAULT_ATTRIBUTES,
	INNER_BLOCKS_TEMPLATE,
} from './constants';
import blockJson from './block.json';
import {
	LocationType,
	WooCommerceBlockLocation,
} from '../product-template/utils';

/**
 * Sets the new query arguments of a Product Query block
 *
 * Shorthand for setting new nested query parameters.
 */
export function setQueryAttribute(
	block: BlockEditProps< ProductCollectionAttributes >,
	queryParams: Partial< ProductCollectionQuery >
) {
	const { query } = block.attributes;

	block.setAttributes( {
		query: {
			...query,
			...queryParams,
		},
	} );
}

const isInProductArchive = () => {
	const ARCHIVE_PRODUCT_TEMPLATES = [
		'woocommerce/woocommerce//archive-product',
		'woocommerce/woocommerce//taxonomy-product_attribute',
		'woocommerce/woocommerce//product-search-results',
		// Custom taxonomy templates have structure:
		// <<THEME>>//taxonomy-product_cat-<<CATEGORY>>
		// hence we're checking if template ID includes the middle part.
		//
		// That includes:
		// - woocommerce/woocommerce//taxonomy-product_cat
		// - woocommerce/woocommerce//taxonomy-product_tag
		'//taxonomy-product_cat',
		'//taxonomy-product_tag',
	];

	const currentTemplateId = select(
		'core/edit-site'
	)?.getEditedPostId() as string;

	/**
	 * Set inherit value when Product Collection block is first added to the page.
	 * We want inherit value to be true when block is added to ARCHIVE_PRODUCT_TEMPLATES
	 * and false when added to somewhere else.
	 */
	if ( currentTemplateId ) {
		return ARCHIVE_PRODUCT_TEMPLATES.some( ( template ) =>
			currentTemplateId.includes( template )
		);
	}

	return false;
};

const isFirstBlockThatUsesPageContext = (
	property: 'inherit' | 'filterable'
) => {
	// We use experimental selector because it's been graduated as stable (`getBlocksByName`)
	// in Gutenberg 17.6 (https://github.com/WordPress/gutenberg/pull/58156) and will be
	// available in WordPress 6.5.
	// Created issue for that: https://github.com/woocommerce/woocommerce/issues/44768.
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore No types for this exist yet, natively.
	const { __experimentalGetGlobalBlocksByName, getBlock } =
		select( blockEditorStore );
	const productCollectionBlockIDs = __experimentalGetGlobalBlocksByName(
		'woocommerce/product-collection'
	) as string[];

	const blockAlreadySyncedWithQuery = productCollectionBlockIDs.find(
		( clientId ) => {
			const block = getBlock( clientId );

			return block.attributes?.query?.[ property ];
		}
	);

	return ! blockAlreadySyncedWithQuery;
};

export function getDefaultValueOfInherit() {
	return isInProductArchive()
		? isFirstBlockThatUsesPageContext( 'inherit' )
		: false;
}

export function getDefaultValueOfFilterable() {
	return ! isInProductArchive()
		? isFirstBlockThatUsesPageContext( 'filterable' )
		: false;
}

/**
 * Add Product Collection block to the parent or ancestor array of the Core Pagination block.
 * This enhancement allows the Core Pagination block to be available for the Product Collection block.
 */
export const addProductCollectionToQueryPaginationParentOrAncestor = () => {
	if ( isWpVersion( '6.1', '>=' ) ) {
		addFilter(
			'blocks.registerBlockType',
			'woocommerce/add-product-collection-block-to-parent-array-of-pagination-block',
			( blockSettings: Block, blockName: string ) => {
				if ( blockName !== coreQueryPaginationBlockName ) {
					return blockSettings;
				}

				if ( blockSettings?.ancestor ) {
					return {
						...blockSettings,
						ancestor: [ ...blockSettings.ancestor, blockJson.name ],
					};
				}

				// Below condition is to support WP >=6.4 where Pagination specifies the parent.
				// Can be removed when minimum WP version is set to 6.5 and higher.
				if ( blockSettings?.parent ) {
					return {
						...blockSettings,
						parent: [ ...blockSettings.parent, blockJson.name ],
					};
				}

				return blockSettings;
			}
		);
	}
};

/**
 * Get the message to show in the preview label when the block is in preview mode based
 * on the `usesReference` value.
 */
export const getUsesReferencePreviewMessage = (
	location: WooCommerceBlockLocation,
	isUsingReferencePreviewMode: boolean
) => {
	if ( isUsingReferencePreviewMode ) {
		if ( location.type === LocationType.Product ) {
			return __(
				'Actual products will vary depending on the product being viewed.',
				'woocommerce'
			);
		}

		return __(
			'Actual products will vary depending on the page being viewed.',
			'woocommerce'
		);
	}

	return '';
};

export const useProductCollectionUIState = ( {
	location,
	usesReference,
	attributes,
	hasInnerBlocks,
}: {
	location: WooCommerceBlockLocation;
	usesReference?: string[] | undefined;
	attributes: ProductCollectionAttributes;
	hasInnerBlocks: boolean;
} ) => {
	// Fetch product to check if it's deleted.
	// `product` will be undefined if it doesn't exist.
	const productId = attributes.query?.productReference;
	const { product, hasResolved } = useSelect(
		( selectFunc ) => {
			if ( ! productId ) {
				return { product: null, hasResolved: true };
			}

			const { getEntityRecord, hasFinishedResolution } =
				selectFunc( coreDataStore );
			const selectorArgs = [ 'postType', 'product', productId ];
			return {
				product: getEntityRecord( ...selectorArgs ),
				hasResolved: hasFinishedResolution(
					'getEntityRecord',
					selectorArgs
				),
			};
		},
		[ productId ]
	);

	const productCollectionUIStateInEditor = useMemo( () => {
		const isInRequiredLocation = usesReference?.includes( location.type );
		const isCollectionSelected = !! attributes.collection;

		/**
		 * Case 1: Product context picker
		 */
		const isProductContextRequired = usesReference?.includes( 'product' );
		const isProductContextSelected =
			( attributes.query?.productReference ?? null ) !== null;
		if (
			isCollectionSelected &&
			isProductContextRequired &&
			! isInRequiredLocation &&
			! isProductContextSelected
		) {
			return ProductCollectionUIStatesInEditor.PRODUCT_REFERENCE_PICKER;
		}

		// Case 2: Deleted product reference
		if (
			isCollectionSelected &&
			isProductContextRequired &&
			! isInRequiredLocation &&
			isProductContextSelected
		) {
			const isProductDeleted =
				productId &&
				( product === undefined || product?.status === 'trash' );
			if ( isProductDeleted ) {
				return ProductCollectionUIStatesInEditor.DELETED_PRODUCT_REFERENCE;
			}
		}

		/**
		 * Case 3: Preview mode - based on `usesReference` value
		 */
		if ( isInRequiredLocation ) {
			/**
			 * Block shouldn't be in preview mode when:
			 * 1. Current location is archive and termId is available.
			 * 2. Current location is product and productId is available.
			 *
			 * Because in these cases, we have required context on the editor side.
			 */
			const isArchiveLocationWithTermId =
				location.type === LocationType.Archive &&
				( location.sourceData?.termId ?? null ) !== null;
			const isProductLocationWithProductId =
				location.type === LocationType.Product &&
				( location.sourceData?.productId ?? null ) !== null;

			if (
				! isArchiveLocationWithTermId &&
				! isProductLocationWithProductId
			) {
				return ProductCollectionUIStatesInEditor.VALID_WITH_PREVIEW;
			}
		}

		/**
		 * Case 4: Collection chooser
		 */
		if ( ! hasInnerBlocks && ! isCollectionSelected ) {
			return ProductCollectionUIStatesInEditor.COLLECTION_PICKER;
		}

		return ProductCollectionUIStatesInEditor.VALID;
	}, [
		location.type,
		location.sourceData?.termId,
		location.sourceData?.productId,
		usesReference,
		attributes.collection,
		productId,
		product,
		hasInnerBlocks,
		attributes.query?.productReference,
	] );

	return { productCollectionUIStateInEditor, isLoading: ! hasResolved };
};

export const useSetPreviewState = ( {
	setPreviewState,
	location,
	attributes,
	setAttributes,
	isUsingReferencePreviewMode,
}: {
	setPreviewState?: SetPreviewState | undefined;
	location: WooCommerceBlockLocation;
	attributes: ProductCollectionAttributes;
	setAttributes: (
		attributes: Partial< ProductCollectionAttributes >
	) => void;
	usesReference?: string[] | undefined;
	isUsingReferencePreviewMode: boolean;
} ) => {
	const setState = ( newPreviewState: PreviewState ) => {
		setAttributes( {
			__privatePreviewState: {
				...attributes.__privatePreviewState,
				...newPreviewState,
			},
		} );
	};

	/**
	 * When usesReference is available on Frontend but not on Editor side,
	 * we want to show a preview label to indicate that the block is in preview mode.
	 */
	const usesReferencePreviewMessage = getUsesReferencePreviewMessage(
		location,
		isUsingReferencePreviewMode
	);
	useLayoutEffect( () => {
		if ( isUsingReferencePreviewMode ) {
			setAttributes( {
				__privatePreviewState: {
					isPreview: usesReferencePreviewMessage.length > 0,
					previewMessage: usesReferencePreviewMessage,
				},
			} );
		}
	}, [
		setAttributes,
		usesReferencePreviewMessage,
		isUsingReferencePreviewMode,
	] );

	// Running setPreviewState function provided by Collection, if it exists.
	useLayoutEffect( () => {
		if ( ! setPreviewState && ! isUsingReferencePreviewMode ) {
			return;
		}

		const cleanup = setPreviewState?.( {
			setState,
			location,
			attributes,
		} );

		if ( cleanup ) {
			return cleanup;
		}

		// It should re-run only when setPreviewState changes to avoid performance issues.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ setPreviewState ] );

	/**
	 * For all Product Collection blocks that inherit query from the template,
	 * we want to show a preview message in the editor if the block is in
	 * generic archive template i.e.
	 * - Products by category
	 * - Products by tag
	 * - Products by attribute
	 */
	const termId =
		location.type === LocationType.Archive
			? location.sourceData?.termId
			: null;
	useLayoutEffect( () => {
		if ( ! setPreviewState && ! isUsingReferencePreviewMode ) {
			const isGenericArchiveTemplate =
				location.type === LocationType.Archive && termId === null;

			setAttributes( {
				__privatePreviewState: {
					isPreview: isGenericArchiveTemplate
						? !! attributes?.query?.inherit
						: false,
					previewMessage: __(
						'Actual products will vary depending on the page being viewed.',
						'woocommerce'
					),
				},
			} );
		}
	}, [
		attributes?.query?.inherit,
		usesReferencePreviewMessage,
		termId,
		location.type,
		setAttributes,
		setPreviewState,
		isUsingReferencePreviewMode,
	] );
};

export const getDefaultQuery = (
	currentQuery: ProductCollectionQuery
): ProductCollectionQuery => ( {
	...currentQuery,
	orderBy: DEFAULT_QUERY.orderBy as TProductCollectionOrderBy,
	order: DEFAULT_QUERY.order as TProductCollectionOrder,
	inherit: getDefaultValueOfInherit(),
	filterable: getDefaultValueOfFilterable(),
} );

export const getDefaultDisplayLayout = () =>
	DEFAULT_ATTRIBUTES.displayLayout as ProductCollectionDisplayLayout;

export const getDefaultSettings = (
	currentAttributes: ProductCollectionAttributes
): Partial< ProductCollectionAttributes > => ( {
	displayLayout: getDefaultDisplayLayout(),
	query: getDefaultQuery( currentAttributes.query ),
} );

export const getDefaultProductCollection = () =>
	createBlock(
		blockJson.name,
		{
			...DEFAULT_ATTRIBUTES,
			query: {
				...DEFAULT_ATTRIBUTES.query,
				inherit: getDefaultValueOfInherit(),
				filterable: getDefaultValueOfFilterable(),
			},
		},
		createBlocksFromInnerBlocksTemplate( INNER_BLOCKS_TEMPLATE )
	);

export const useGetProduct = ( productId: number | undefined ) => {
	const [ product, setProduct ] = useState< ProductResponseItem | null >(
		null
	);
	const [ isLoading, setIsLoading ] = useState< boolean >( false );

	useEffect( () => {
		const fetchProduct = async () => {
			if ( productId ) {
				setIsLoading( true );
				try {
					const fetchedProduct = ( await getProduct(
						productId
					) ) as ProductResponseItem;
					setProduct( fetchedProduct );
				} catch ( error ) {
					setProduct( null );
				} finally {
					setIsLoading( false );
				}
			} else {
				setProduct( null );
				setIsLoading( false );
			}
		};

		fetchProduct();
	}, [ productId ] );

	return { product, isLoading };
};
