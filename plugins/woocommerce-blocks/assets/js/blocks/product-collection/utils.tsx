/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { addFilter } from '@wordpress/hooks';
import { select } from '@wordpress/data';
import { isWpVersion } from '@woocommerce/settings';
import type { BlockEditProps, Block } from '@wordpress/blocks';
import { useLayoutEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
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
 * Get the preview message for the Product Collection block based on the usesReference.
 * There are two scenarios:
 * 1. When usesReference is product, the preview message will be:
 * 	  "Actual products will vary depending on the product being viewed."
 * 2. For all other usesReference, the preview message will be:
 *    "Actual products will vary depending on the page being viewed."
 *
 * This message will be shown when the usesReference isn't available on the Editor side, but is available on the Frontend.
 */
export const getUsesReferencePreviewMessage = (
	location: WooCommerceBlockLocation,
	usesReference?: string[]
) => {
	if ( ! ( Array.isArray( usesReference ) && usesReference.length > 0 ) ) {
		return '';
	}

	if ( usesReference.includes( location.type ) ) {
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
		if ( isArchiveLocationWithTermId || isProductLocationWithProductId ) {
			return '';
		}

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

export const useSetPreviewState = ( {
	setPreviewState,
	location,
	attributes,
	setAttributes,
	usesReference,
}: {
	setPreviewState?: SetPreviewState | undefined;
	location: WooCommerceBlockLocation;
	attributes: ProductCollectionAttributes;
	setAttributes: (
		attributes: Partial< ProductCollectionAttributes >
	) => void;
	usesReference?: string[] | undefined;
} ) => {
	const setState = ( newPreviewState: PreviewState ) => {
		setAttributes( {
			__privatePreviewState: {
				...attributes.__privatePreviewState,
				...newPreviewState,
			},
		} );
	};
	const isCollectionUsesReference =
		usesReference && usesReference?.length > 0;

	/**
	 * When usesReference is available on Frontend but not on Editor side,
	 * we want to show a preview label to indicate that the block is in preview mode.
	 */
	const usesReferencePreviewMessage = getUsesReferencePreviewMessage(
		location,
		usesReference
	);
	useLayoutEffect( () => {
		if ( isCollectionUsesReference ) {
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
		isCollectionUsesReference,
	] );

	// Running setPreviewState function provided by Collection, if it exists.
	useLayoutEffect( () => {
		if ( ! setPreviewState && ! isCollectionUsesReference ) {
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
	useLayoutEffect( () => {
		if ( ! setPreviewState && ! isCollectionUsesReference ) {
			const isGenericArchiveTemplate =
				location.type === LocationType.Archive &&
				location.sourceData?.termId === null;

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
		location.sourceData?.termId,
		location.type,
		setAttributes,
		setPreviewState,
		isCollectionUsesReference,
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
