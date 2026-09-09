/**
 * External dependencies
 */
import {
	useBlockProps,
	useInnerBlocksProps,
	BlockContextProvider,
} from '@wordpress/block-editor';
import { withSpokenMessages } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { useCollectionData } from '@woocommerce/base-context/hooks';
import { objectHasProp } from '@woocommerce/types';
import type { SelectableItemsContext } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { TaxonomyFilterInspectorControls } from './inspector';
import { termOptionsPreview } from './constants';
import { EditProps } from './types';
import { getAllowedBlocks } from '../../utils/get-allowed-blocks';
import { EXCLUDED_BLOCKS } from '../../constants';
import type { FilterOptionItem, FilterItemFields } from '../../types';
import { InitialDisabled } from '../../components/initial-disabled';
import { Notice } from '../../components/notice';
import { getTaxonomyLabel } from './utils';
import { sortFilterOptions } from '../../utils/sort-filter-options';
import { createHierarchicalList } from '../../utils/create-hierarchical-list';
import { getProductCollectionQueryState } from '../../utils/get-product-collection-query-state';

type WPTaxonomyTerm = {
	id: number;
	name: string;
	slug: string;
	parent: number;
	menu_order?: number;
};

// Module-level stable references for the taxonomy-terms useSelect below.
// Avoids allocating fresh objects on every selector invocation, which would
// trip @wordpress/data's SCRIPT_DEBUG unstable-reference check. Frozen so an
// accidental mutation in a consumer cannot leak across renders or instances.
const EMPTY_TAXONOMY_TERMS: readonly WPTaxonomyTerm[] = Object.freeze(
	[] as WPTaxonomyTerm[]
);
const EMPTY_TAXONOMY_TERMS_RESULT = Object.freeze( {
	taxonomyTerms: EMPTY_TAXONOMY_TERMS,
	isTermsLoading: false,
} );

const Edit = ( props: EditProps ) => {
	const { attributes: blockAttributes } = props;

	const {
		taxonomy,
		isPreview,
		displayStyle,
		showCounts,
		sortOrder,
		hideEmpty,
	} = blockAttributes;

	const [ termOptions, setTermOptions ] = useState< FilterOptionItem[] >(
		isPreview
			? sortFilterOptions( [ ...termOptionsPreview ], sortOrder )
			: []
	);
	const [ isOptionsLoading, setIsOptionsLoading ] = useState< boolean >(
		! isPreview
	);
	const localQueryState = getProductCollectionQueryState(
		props.context.query
	);
	const { data: filteredCounts, isLoading: isFilterCountsLoading } =
		useCollectionData( {
			queryTaxonomy: isPreview ? undefined : taxonomy,
			queryState: localQueryState ?? {},
			isEditor: true,
		} );
	const shouldScopeTerms = localQueryState !== null && hideEmpty;
	const includedTermIds =
		shouldScopeTerms &&
		objectHasProp( filteredCounts, 'taxonomy_counts' ) &&
		Array.isArray( filteredCounts.taxonomy_counts )
			? filteredCounts.taxonomy_counts
					.filter( ( { count } ) => count > 0 )
					.map( ( { term } ) => term )
					.sort( ( a, b ) => a - b )
					.join( ',' )
			: '';

	// Fetch taxonomy terms using WordPress core data
	const { taxonomyTerms, isTermsLoading } = useSelect(
		( select ) => {
			if (
				isPreview ||
				! taxonomy ||
				( shouldScopeTerms &&
					( isFilterCountsLoading || ! includedTermIds ) )
			) {
				return EMPTY_TAXONOMY_TERMS_RESULT;
			}

			const { getEntityRecords, hasFinishedResolution } =
				select( coreStore );

			const selectArgs = {
				per_page: shouldScopeTerms ? -1 : 15,
				...( shouldScopeTerms && { include: includedTermIds } ),
				hide_empty: hideEmpty,
				orderby: 'name',
				order: 'asc',
			};
			return {
				taxonomyTerms:
					( getEntityRecords( 'taxonomy', taxonomy, selectArgs ) as
						| WPTaxonomyTerm[]
						| null ) || EMPTY_TAXONOMY_TERMS,
				isTermsLoading: ! hasFinishedResolution( 'getEntityRecords', [
					'taxonomy',
					taxonomy,
					selectArgs,
				] ),
			};
		},
		[
			taxonomy,
			hideEmpty,
			isPreview,
			shouldScopeTerms,
			isFilterCountsLoading,
			includedTermIds,
		]
	);

	useEffect( () => {
		if ( isPreview ) {
			// In preview mode, use the preview data directly
			const previewItems = termOptionsPreview.map( ( item ) => {
				if ( showCounts ) {
					return item;
				}
				// Strip count when showCounts is false
				const { count, ...rest } = item;
				return rest;
			} );
			setTermOptions( sortFilterOptions( previewItems, sortOrder ) );
			setIsOptionsLoading( false );
			return;
		}

		if ( isTermsLoading || isFilterCountsLoading ) {
			setIsOptionsLoading( true );
			return;
		}

		if ( ! taxonomyTerms.length ) {
			setTermOptions( [] );
			setIsOptionsLoading( false );
			return;
		}

		// Get taxonomy counts from the API response
		const taxonomyCounts =
			objectHasProp( filteredCounts, 'taxonomy_counts' ) &&
			Array.isArray( filteredCounts.taxonomy_counts )
				? filteredCounts.taxonomy_counts
				: [];

		// Process the terms
		const processedTerms = taxonomyTerms.reduce(
			( acc: FilterOptionItem[], term ) => {
				const count =
					taxonomyCounts.find( ( item ) => item.term === term.id )
						?.count || 0;

				// If hideEmpty is true and count is 0, exclude this term
				if ( hideEmpty && count === 0 ) {
					return acc;
				}

				acc.push( {
					label: term.name,
					value: term.slug,
					selected: false,
					...( showCounts && { count } ),
					id: String( term.id ),
					termId: term.id,
					parent: term.parent || 0,
					menuOrder: term.menu_order ?? 0,
				} );

				return acc;
			},
			[]
		);

		// Create hierarchical structure then apply sorting
		const hierarchicalTerms = createHierarchicalList(
			processedTerms,
			sortOrder
		);
		setTermOptions( hierarchicalTerms );
		setIsOptionsLoading( false );
	}, [
		taxonomy,
		taxonomyTerms,
		filteredCounts,
		sortOrder,
		hideEmpty,
		showCounts,
		isPreview,
		isTermsLoading,
		isFilterCountsLoading,
	] );

	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		useBlockProps(),
		{
			allowedBlocks: getAllowedBlocks( EXCLUDED_BLOCKS ),
			template: [
				[
					'core/heading',
					{
						level: 3,
						content: getTaxonomyLabel( taxonomy ),
						style: {
							spacing: {
								margin: {
									bottom: '0.625rem',
									top: '0',
								},
							},
						},
					},
				],
				[ displayStyle ],
			],
		}
	);

	const isLoading = isPreview
		? false
		: isTermsLoading || isFilterCountsLoading || isOptionsLoading;

	if ( ! taxonomy )
		return (
			<div { ...innerBlocksProps }>
				<TaxonomyFilterInspectorControls { ...props } />
				<Notice>
					<p>
						{ __(
							'Please select a taxonomy to use this filter!',
							'woocommerce'
						) }
					</p>
				</Notice>
			</div>
		);

	if ( ! isLoading && ! isPreview && taxonomyTerms.length === 0 )
		return (
			<div { ...innerBlocksProps }>
				<TaxonomyFilterInspectorControls { ...props } />
				<Notice>
					<p>
						{ sprintf(
							// translators: %s: Taxonomy label.
							__(
								'There are no products associated with %s.',
								'woocommerce'
							),
							getTaxonomyLabel( taxonomy )
						) }
					</p>
				</Notice>
			</div>
		);

	return (
		<div { ...innerBlocksProps }>
			<TaxonomyFilterInspectorControls { ...props } />
			<InitialDisabled>
				<BlockContextProvider
					value={ {
						'woocommerce/selectableItems': {
							items:
								termOptions.length === 0 && isPreview
									? termOptionsPreview
									: termOptions,
							selectionMode: 'multiple' as const,
							storeNamespace: 'woocommerce/product-filters',
							isLoading,
						} satisfies SelectableItemsContext< FilterItemFields >,
					} }
				>
					{ children }
				</BlockContextProvider>
			</InitialDisabled>
		</div>
	);
};

export default withSpokenMessages( Edit );
