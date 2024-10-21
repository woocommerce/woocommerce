/**
 * External dependencies
 */
import {
	useCollection,
	useCollectionData,
} from '@woocommerce/base-context/hooks';
import {
	AttributeSetting,
	AttributeTerm,
	objectHasProp,
} from '@woocommerce/types';
import {
	useBlockProps,
	useInnerBlocksProps,
	BlockContextProvider,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { withSpokenMessages } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { useDispatch, useSelect } from '@wordpress/data';
import { BlockAttributes, createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Inspector } from './inspector';
import { attributeOptionsPreview } from './constants';
import './style.scss';
import { EditProps, isAttributeCounts } from './types';
import { getAttributeFromId } from './utils';
import { getAllowedBlocks } from '../../utils';
import { EXCLUDED_BLOCKS } from '../../constants';
import { FilterOptionItem } from '../../types';
import { InitialDisabled } from '../../components/initial-disabled';
import { Notice } from '../../components/notice';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

function findClientIdByName(
	block: BlockAttributes,
	targetName: string
): string | undefined {
	if ( block.name === targetName ) {
		return block.clientId;
	}

	if ( block.innerBlocks && block.innerBlocks.length > 0 ) {
		for ( const innerBlock of block.innerBlocks ) {
			const blockId: string | undefined = findClientIdByName(
				innerBlock,
				targetName
			);
			if ( blockId ) {
				return blockId;
			}
		}
	}

	return undefined;
}

const Edit = ( props: EditProps ) => {
	const { attributes: blockAttributes, clientId } = props;

	const {
		attributeId,
		queryType,
		isPreview,
		displayStyle,
		showCounts,
		sortOrder,
		hideEmpty,
		clearButton,
	} = blockAttributes;

	const attributeObject = getAttributeFromId( attributeId );

	const [ attributeOptions, setAttributeOptions ] = useState<
		FilterOptionItem[]
	>( [] );
	const [ isOptionsLoading, setIsOptionsLoading ] =
		useState< boolean >( true );
	const [ previousClearButtonState, setPreviousClearButtonState ] =
		useState< boolean >( clearButton );

	const { clearButtonBlockId } = useSelect( ( select ) => {
		const { getBlock } = select( blockEditorStore );
		const attributeFilterBlock = getBlock( clientId );
		const clearButtonId = findClientIdByName(
			attributeFilterBlock,
			'woocommerce/product-filter-clear-button'
		);

		return {
			clearButtonBlockId: clearButtonId,
		};
	} );

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
	// @ts-expect-error @wordpress/data types are outdated.
	const { insertBlock, removeBlock, updateBlockAttributes } =
		useDispatch( blockEditorStore );

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
					.map( ( term, index ) => ( {
						label: showCounts
							? `${ term.name } (${ term.count })`
							: term.name,
						value: term.id.toString(),
						selected: index === 1,
						rawData: term,
					} ) )
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
	] );

	useEffect( () => {
		if ( clearButton !== previousClearButtonState ) {
			if ( clearButton ) {
				insertBlock(
					createBlock( 'woocommerce/product-filter-clear-button' ),
					1
				);
			} else if (
				clearButton === false &&
				Boolean( clearButtonBlockId )
			) {
				updateBlockAttributes( clearButtonBlockId, {
					lock: { remove: false, move: false },
				} );
				removeBlock( clearButtonBlockId );
			}
		}
	}, [
		clearButton,
		previousClearButtonState,
		clearButtonBlockId,
		insertBlock,
		removeBlock,
	] );

	const { children, ...innerBlocksProps } = useInnerBlocksProps(
		useBlockProps(),
		{
			allowedBlocks: getAllowedBlocks( EXCLUDED_BLOCKS ),
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
						filterData: {
							items:
								attributeOptions.length === 0 && isPreview
									? attributeOptionsPreview
									: attributeOptions,
							isLoading,
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
