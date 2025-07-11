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
import { __ } from '@wordpress/i18n';
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
		[]
	);
	const [ isOptionsLoading, setIsOptionsLoading ] =
		useState< boolean >( true );

	// Fetch taxonomy terms using WordPress core data
	const { taxonomyTerms, isTermsLoading } = useSelect(
		( select ) => {
			if ( ! taxonomy || isPreview ) {
				return { taxonomyTerms: [], isTermsLoading: false };
			}

			const { getEntityRecords, hasFinishedResolution } =
				select( coreStore );
			const selectorArgs = [
				'taxonomy',
				taxonomy,
				{
					per_page: -1,
					hide_empty: hideEmpty,
					orderby: 'name',
					order: 'asc',
				},
			];

			return {
				taxonomyTerms: getEntityRecords( ...selectorArgs ) || [],
				isTermsLoading: ! hasFinishedResolution(
					'getEntityRecords',
					selectorArgs
				),
			};
		},
		[ taxonomy, hideEmpty, isPreview ]
	);

	// Fetch taxonomy counts using the updated useCollectionData hook
	const { data: filteredCounts, isLoading: isFilterCountsLoading } =
		useCollectionData( {
			queryTaxonomy: taxonomy,
			queryState: {},
			isEditor: true,
		} );

	useEffect( () => {
		if ( isPreview ) {
			// Use preview data for preview mode
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

		if ( ! taxonomy || ! taxonomyTerms.length ) {
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

		// Create a map for quick lookup of counts by term ID
		const countsMap = new Map(
			taxonomyCounts.map( ( item ) => [ item.term, item.count ] )
		);

		// Process the terms
		const processedTerms = taxonomyTerms
			.map( ( term ): FilterOptionItem | null => {
				const count = countsMap.get( term.id ) || 0;

				// If hideEmpty is true and count is 0, exclude this term
				if ( hideEmpty && count === 0 ) {
					return null;
				}

				return {
					label: term.name,
					value: term.slug,
					selected: false,
					count,
				};
			} )
			.filter( ( term ): term is FilterOptionItem => term !== null );

		// Sort the processed terms
		setTermOptions( sortFilterOptions( processedTerms, sortOrder ) );
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

	const isLoading =
		isTermsLoading || isFilterCountsLoading || isOptionsLoading;

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
						{ __(
							'There are no products with the selected taxonomy.',
							'woocommerce'
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
