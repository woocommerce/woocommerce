/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import {
	useBlockProps,
	useInnerBlocksProps,
	BlockContextProvider,
} from '@wordpress/block-editor';
import { withSpokenMessages } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import { Inspector } from './inspector';
import { attributeOptionsPreview } from './constants';
import {
	DEFAULT_DISPLAY_STYLE,
	DEFAULT_QUERY_TYPE,
	DEFAULT_SORT_ORDER,
	EditProps,
	AttributeSetting,
	AttributeTerm,
	isAttributeCounts,
} from './types';
import { getAttributeFromId } from './utils';
import { getAllowedBlocks } from '../shared/product-filters/utils/get-allowed-blocks';
import { EXCLUDED_BLOCKS } from '../shared/product-filters/constants';
import type {
	FilterOptionItem,
	FilterItemFields,
	SelectableItemsContext,
} from '../shared/product-filters/types';
import { InitialDisabled } from '../shared/product-filters/components/initial-disabled';
import { Notice } from '../shared/product-filters/components/notice';
import { sortFilterOptions } from '../shared/product-filters/utils/sort-filter-options';
import { useCollectionData } from '../shared/product-filters/hooks/use-collection-data';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

function useAttributeTerms(
	attributeId: number | undefined,
	hideEmpty: boolean
) {
	const [ results, setResults ] = useState< AttributeTerm[] >( [] );
	const [ isLoading, setIsLoading ] = useState( Boolean( attributeId ) );

	useEffect( () => {
		if ( ! attributeId ) {
			setResults( [] );
			setIsLoading( false );
			return;
		}

		let isMounted = true;

		setIsLoading( true );
		apiFetch( {
			path: addQueryArgs(
				`/wc/store/v1/products/attributes/${ attributeId }/terms`,
				{
					orderby: 'menu_order',
					hide_empty: hideEmpty,
					__experimental_visual: true,
				}
			),
		} )
			.then( ( response ) => {
				if ( isMounted ) {
					setResults( response as AttributeTerm[] );
				}
			} )
			.catch( () => {
				if ( isMounted ) {
					setResults( [] );
				}
			} )
			.finally( () => {
				if ( isMounted ) {
					setIsLoading( false );
				}
			} );

		return () => {
			isMounted = false;
		};
	}, [ attributeId, hideEmpty ] );

	return { results, isLoading };
}

const Edit = ( props: EditProps ) => {
	const { attributes: blockAttributes } = props;

	const {
		attributeId = 0,
		queryType = DEFAULT_QUERY_TYPE,
		isPreview = false,
		displayStyle = DEFAULT_DISPLAY_STYLE,
		showCounts = false,
		sortOrder = DEFAULT_SORT_ORDER,
		hideEmpty = true,
	} = blockAttributes;

	const attributeObject = getAttributeFromId( attributeId );

	const [ attributeOptions, setAttributeOptions ] = useState<
		FilterOptionItem[]
	>( [] );
	const [ isOptionsLoading, setIsOptionsLoading ] =
		useState< boolean >( true );

	const { results: attributeTerms, isLoading: isTermsLoading } =
		useAttributeTerms( attributeObject?.id, hideEmpty );

	const { data: filteredCounts, isLoading: isFilterCountsLoading } =
		useCollectionData( {
			queryAttribute: {
				taxonomy: attributeObject?.taxonomy || '',
				queryType,
			},
			queryState: {},
			isEditor: true,
		} );

	useEffect( () => {
		if ( isTermsLoading || isFilterCountsLoading ) return;

		const termIdHasProducts = isAttributeCounts(
			filteredCounts.attribute_counts
		)
			? filteredCounts.attribute_counts.map( ( term ) => term.term )
			: [];

		if ( termIdHasProducts.length === 0 && hideEmpty ) {
			setAttributeOptions( [] );
		} else {
			const filteredOptions = attributeTerms
				.filter( ( term ) => {
					if ( hideEmpty )
						return termIdHasProducts.includes( term.id );
					return true;
				} )
				.map( ( term, index ) => ( {
					id: term.id.toString(),
					label: term.name,
					value: term.id.toString(),
					selected: index === 0,
					...( showCounts && { count: term.count } ),
					...( term.__experimentalVisual && {
						visual: term.__experimentalVisual,
					} ),
				} ) );

			setAttributeOptions(
				sortFilterOptions( filteredOptions, sortOrder )
			);
		}

		setIsOptionsLoading( false );
	}, [
		showCounts,
		attributeTerms,
		filteredCounts,
		sortOrder,
		hideEmpty,
		isTermsLoading,
		isFilterCountsLoading,
		attributeObject,
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
						content:
							attributeObject?.label ||
							__( 'Attribute', 'woocommerce' ),
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

	if ( Object.keys( ATTRIBUTES ).length === 0 )
		return (
			<div { ...innerBlocksProps }>
				<Inspector { ...props } />
				<Notice>
					<p>
						{ __(
							"Attributes are needed for filtering your products. You haven't created any attributes yet.",
							'woocommerce'
						) }
					</p>
				</Notice>
			</div>
		);

	if ( ! attributeId || ! attributeObject )
		return (
			<div { ...innerBlocksProps }>
				<Inspector { ...props } />
				<Notice>
					<p>
						{ __(
							'Please select an attribute to use this filter!',
							'woocommerce'
						) }
					</p>
				</Notice>
			</div>
		);

	if ( ! isLoading && attributeTerms.length === 0 )
		return (
			<div { ...innerBlocksProps }>
				<Inspector { ...props } />
				<Notice>
					<p>
						{ __(
							'There are no products with the selected attributes.',
							'woocommerce'
						) }
					</p>
				</Notice>
			</div>
		);

	return (
		<div { ...innerBlocksProps }>
			<Inspector { ...props } />
			<InitialDisabled>
				<BlockContextProvider
					value={ {
						'woocommerce/selectableItems': {
							items:
								attributeOptions.length === 0 && isPreview
									? attributeOptionsPreview
									: attributeOptions,
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
