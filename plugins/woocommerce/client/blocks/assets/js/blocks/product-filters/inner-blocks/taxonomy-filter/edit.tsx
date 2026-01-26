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

/**
 * Internal dependencies
 */
import { TaxonomyFilterInspectorControls } from './inspector';
import { termOptionsPreview } from './constants';
import { EditProps } from './types';
import { getAllowedBlocks } from '../../utils/get-allowed-blocks';
import { EXCLUDED_BLOCKS } from '../../constants';
import type { FilterOptionItem } from '../../types';
import { InitialDisabled } from '../../components/initial-disabled';
import { Notice } from '../../components/notice';
import { getTaxonomyLabel } from './utils';
import { sortFilterOptions } from '../../utils/sort-filter-options';

// Create hierarchical structure: parents followed by their children
function createHierarchicalList(
	terms: FilterOptionItem[],
	sortOrder: string
) {
	const children = new Map();

	// First: categorize terms
	terms.forEach( ( term ) => {
		if ( ! children.has( term.parent ) ) {
			children.set( term.parent, [] );
		}
		children.get( term.parent ).push( term );
	} );

	// Next: sort them
	children.keys().forEach( ( key ) => {
		children.set(
			key,
			sortFilterOptions( children.get( key ), sortOrder )
		);
	} );

	// Last: build hierarchical list
	const result: FilterOptionItem[] = [];
	function addTermsRecursively(
		termList: FilterOptionItem[],
		depth = 0,
		visited = new Set< number >()
	) {
		if ( depth > 10 ) {
			return;
		}
		termList.forEach( ( term ) => {
			if ( ! term.id || visited.has( term.id ) ) {
				return;
			}
			visited.add( term.id );
			result.push( { ...term, depth } );
			const termChildren = children.get( term.id ) || [];
			if ( termChildren.length > 0 ) {
				addTermsRecursively( termChildren, depth + 1, visited );
			}
		} );
	}

	addTermsRecursively( children.get( 0 ) );
	return result;
}

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

	// Fetch taxonomy terms using WordPress core data
	const { taxonomyTerms, isTermsLoading } = useSelect(
		( select ) => {
			if ( isPreview || ! taxonomy ) {
				return { taxonomyTerms: [], isTermsLoading: false };
			}

			const { getEntityRecords, hasFinishedResolution } =
				select( coreStore );

			const selectArgs = {
				per_page: 15,
				hide_empty: hideEmpty,
				orderby: 'name',
				order: 'asc',
			};
			return {
				taxonomyTerms:
					getEntityRecords( 'taxonomy', taxonomy, selectArgs ) || [],
				isTermsLoading: ! hasFinishedResolution( 'getEntityRecords', [
					'taxonomy',
					taxonomy,
					selectArgs,
				] ),
			};
		},
		[ taxonomy, hideEmpty, isPreview ]
	);

	// Fetch taxonomy counts using the updated useCollectionData hook
	const { data: filteredCounts, isLoading: isFilterCountsLoading } =
		useCollectionData( {
			queryTaxonomy: isPreview ? undefined : taxonomy,
			queryState: {},
			isEditor: true,
		} );

	useEffect( () => {
		if ( isPreview ) {
			// In preview mode, use the preview data directly
			setTermOptions(
				sortFilterOptions( [ ...termOptionsPreview ], sortOrder )
			);
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
					count,
					id: term.id,
					parent: term.parent || 0,
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
						filterData: {
							items:
								termOptions.length === 0 && isPreview
									? termOptionsPreview
									: termOptions,
							isLoading,
							showCounts,
						},
					} }
				>
					{ children }
				</BlockContextProvider>
			</InitialDisabled>
		</div>
	);
};

export default withSpokenMessages( Edit );
