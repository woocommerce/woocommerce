/**
 * External dependencies
 */
import {
	useCollection,
	useCollectionData,
} from '@woocommerce/base-context/hooks';
import { AttributeTerm, objectHasProp } from '@woocommerce/types';
import {
	useBlockProps,
	useInnerBlocksProps,
	BlockContextProvider,
} from '@wordpress/block-editor';
import { withSpokenMessages } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Inspector } from './components/inspector';
import { attributeOptionsPreview } from './constants';
import './style.scss';
import { EditProps, isAttributeCounts } from './types';
import { getAttributeFromId } from './utils';
import { getAllowedBlocks } from '../../utils';
import { DISALLOWED_BLOCKS } from '../../constants';
import { CollectionItem } from '../../types';

const Edit = ( props: EditProps ) => {
	const { attributes: blockAttributes } = props;

	const {
		attributeId,
		queryType,
		isPreview,
		displayStyle,
		showCounts,
		sortOrder,
		hideEmpty,
	} = blockAttributes;

	const attributeObject = getAttributeFromId( attributeId );

	const [ attributeOptions, setAttributeOptions ] = useState<
		AttributeTerm[]
	>( [] );
	const [ isOptionsLoading, setIsOptionsLoading ] =
		useState< boolean >( true );

	const { results: attributeTerms, isLoading: isTermsLoading } =
		useCollection< AttributeTerm >( {
			namespace: '/wc/store/v1',
			resourceName: 'products/attributes/terms',
			resourceValues: [ attributeObject?.id || 0 ],
			shouldSelect: !! attributeObject?.id,
			query: { orderby: 'menu_order', hide_empty: hideEmpty },
		} );

	const { results: filteredCounts, isLoading: isFilterCountsLoading } =
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

		const termIdHasProducts =
			objectHasProp( filteredCounts, 'attribute_counts' ) &&
			isAttributeCounts( filteredCounts.attribute_counts )
				? filteredCounts.attribute_counts.map( ( term ) => term.term )
				: [];

		if ( termIdHasProducts.length === 0 && hideEmpty ) {
			setAttributeOptions( [] );
		} else {
			setAttributeOptions(
				attributeTerms
					.filter( ( term ) => {
						if ( hideEmpty )
							return termIdHasProducts.includes( term.id );
						return true;
					} )
					.sort( ( a, b ) => {
						switch ( sortOrder ) {
							case 'name-asc':
								return a.name > b.name ? 1 : -1;
							case 'name-desc':
								return a.name < b.name ? 1 : -1;
							case 'count-asc':
								return a.count > b.count ? 1 : -1;
							case 'count-desc':
							default:
								return a.count < b.count ? 1 : -1;
						}
					} )
			);
		}

		setIsOptionsLoading( false );
	}, [
		attributeTerms,
		filteredCounts,
		sortOrder,
		hideEmpty,
		isTermsLoading,
		isFilterCountsLoading,
	] );

	const blockProps = useBlockProps();
	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		useBlockProps(),
		{
			allowedBlocks: getAllowedBlocks( DISALLOWED_BLOCKS ),
			template: [
				[
					'core/group',
					{
						layout: {
							type: 'flex',
							flexWrap: 'nowrap',
						},
						metadata: {
							name: __( 'Header', 'woocommerce' ),
						},
						style: {
							spacing: {
								blockGap: '0',
							},
						},
					},
					[
						[
							'core/heading',
							{
								level: 3,
								content:
									attributeObject?.label ||
									__( 'Attribute', 'woocommerce' ),
							},
						],
						[
							'woocommerce/product-filter-clear-button',
							{
								lock: {
									remove: true,
									move: false,
								},
							},
						],
					],
				],
				[
					displayStyle,
					{
						lock: {
							remove: true,
						},
					},
				],
			],
		}
	);

	const filterOptions: CollectionItem[] = (
		isPreview && attributeOptions.length === 0
			? attributeOptionsPreview
			: attributeOptions
	).map( ( option ) => ( {
		label: showCounts
			? `${ option.name } (${ option.count })`
			: option.name,
		value: option.id.toString(),
	} ) );

	return (
		<div { ...innerBlocksProps }>
			<Inspector { ...props } />
			<BlockContextProvider
				value={ {
					filterData: { collection: filterOptions },
					isFilterDataLoading:
						isTermsLoading ||
						isFilterCountsLoading ||
						isOptionsLoading,
					isParentSelected: props.isSelected,
				} }
			>
				{ children }
			</BlockContextProvider>
		</div>
	);
};

export default withSpokenMessages( Edit );
