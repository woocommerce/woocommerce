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

/**
 * Internal dependencies
 */
import {
	PreviewState,
	ProductCollectionAttributes,
	ProductCollectionQuery,
	SetPreviewState,
} from './types';
import { coreQueryPaginationBlockName } from './constants';
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
		'woocommerce/woocommerce//taxonomy-product_cat',
		'woocommerce/woocommerce//taxonomy-product_tag',
		'woocommerce/woocommerce//taxonomy-product_attribute',
		'woocommerce/woocommerce//product-search-results',
	];

	const currentTemplateId = select(
		'core/edit-site'
	)?.getEditedPostId() as string;

	/**
	 * Set inherit value when Product Collection block is first added to the page.
	 * We want inherit value to be true when block is added to ARCHIVE_PRODUCT_TEMPLATES
	 * and false when added to somewhere else.
	 */
	return currentTemplateId
		? ARCHIVE_PRODUCT_TEMPLATES.includes( currentTemplateId )
		: false;
};

const isFirstBlockThatSyncsWithQuery = () => {
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

			return block.attributes?.query?.inherit;
		}
	);

	return ! blockAlreadySyncedWithQuery;
};

export function getDefaultValueOfInheritQueryFromTemplate() {
	return isInProductArchive() ? isFirstBlockThatSyncsWithQuery() : false;
}

/**
 * Add Product Collection block to the parent array of the Core Pagination block.
 * This enhancement allows the Core Pagination block to be available for the Product Collection block.
 */
export const addProductCollectionBlockToParentOfPaginationBlock = () => {
	if ( isWpVersion( '6.1', '>=' ) ) {
		addFilter(
			'blocks.registerBlockType',
			'woocommerce/add-product-collection-block-to-parent-array-of-pagination-block',
			( blockSettings: Block, blockName: string ) => {
				if (
					blockName !== coreQueryPaginationBlockName ||
					! blockSettings?.parent
				) {
					return blockSettings;
				}

				return {
					...blockSettings,
					parent: [ ...blockSettings.parent, blockJson.name ],
				};
			}
		);
	}
};

export const useSetPreviewState = ( {
	setPreviewState,
	location,
	attributes,
	setAttributes,
}: {
	setPreviewState?: SetPreviewState | undefined;
	location: WooCommerceBlockLocation;
	attributes: ProductCollectionAttributes;
	setAttributes: (
		attributes: Partial< ProductCollectionAttributes >
	) => void;
} ) => {
	const setState = ( newPreviewState: PreviewState ) => {
		setAttributes( {
			__privatePreviewState: {
				...attributes.__privatePreviewState,
				...newPreviewState,
			},
		} );
	};

	// Running setPreviewState function provided by Collection, if it exists.
	useLayoutEffect( () => {
		if ( ! setPreviewState ) {
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
		if ( ! setPreviewState ) {
			const isGenericArchiveTemplate =
				location.type === LocationType.Archive &&
				location.sourceData?.termId === null;
			if ( isGenericArchiveTemplate ) {
				setAttributes( {
					__privatePreviewState: {
						isPreview: !! attributes?.query?.inherit,
						previewMessage: __(
							'Actual products will vary depending on the page being viewed.',
							'woocommerce'
						),
					},
				} );
			}
		}
	}, [
		attributes?.query?.inherit,
		location.sourceData?.termId,
		location.type,
		setAttributes,
		setPreviewState,
	] );
};
