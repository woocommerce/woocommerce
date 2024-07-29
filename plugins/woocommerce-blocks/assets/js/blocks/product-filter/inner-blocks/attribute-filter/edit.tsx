/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { useBlockProps } from '@wordpress/block-editor';
import { getSetting } from '@woocommerce/settings';
import {
	useCollection,
	useCollectionData,
} from '@woocommerce/base-context/hooks';
import {
	AttributeSetting,
	AttributeTerm,
	objectHasProp,
} from '@woocommerce/types';
import { Disabled, withSpokenMessages, Notice } from '@wordpress/components';
import { dispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { EditProps, isAttributeCounts } from './types';
import { NoAttributesPlaceholder } from './components/placeholder';
import { getAttributeFromId } from './utils';
import { Inspector } from './components/inspector';
import { AttributeCheckboxList } from './components/attribute-checkbox-list';
import { AttributeDropdown } from './components/attribute-dropdown';
import { attributeOptionsPreview } from './constants';
import './style.scss';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

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
	} = blockAttributes;

	const attributeObject = getAttributeFromId( attributeId );

	const [ attributeOptions, setAttributeOptions ] = useState<
		AttributeTerm[]
	>( [] );

	const { updateBlockAttributes } = dispatch( 'core/block-editor' );

	const { results: attributeTerms } = useCollection< AttributeTerm >( {
		namespace: '/wc/store/v1',
		resourceName: 'products/attributes/terms',
		resourceValues: [ attributeObject?.id || 0 ],
		shouldSelect: blockAttributes.attributeId > 0,
		query: { orderby: 'menu_order', hide_empty: hideEmpty },
	} );

	const { results: filteredCounts } = useCollectionData( {
		queryAttribute: {
			taxonomy: attributeObject?.taxonomy || '',
			queryType,
		},
		queryState: {},
		isEditor: true,
	} );

	const { productFilterWrapperBlockId, productFilterWrapperHeadingBlockId } =
		useSelect(
			( select ) => {
				if ( ! clientId )
					return {
						productFilterWrapperBlockId: undefined,
						productFilterWrapperHeadingBlockId: undefined,
					};

				const { getBlockParentsByBlockName, getBlock } =
					select( 'core/block-editor' );

				const parentBlocksByBlockName = getBlockParentsByBlockName(
					clientId,
					'woocommerce/product-filter'
				);

				if ( parentBlocksByBlockName.length === 0 )
					return {
						productFilterWrapperBlockId: undefined,
						productFilterWrapperHeadingBlockId: undefined,
					};

				const parentBlockId = parentBlocksByBlockName[ 0 ];

				const parentBlock = getBlock( parentBlockId );
				const headerGroupBlock = parentBlock?.innerBlocks.find(
					( block ) => block.name === 'core/group'
				);
				const headingBlock = headerGroupBlock?.innerBlocks.find(
					( block ) => block.name === 'core/heading'
				);

				return {
					productFilterWrapperBlockId: parentBlockId,
					productFilterWrapperHeadingBlockId: headingBlock?.clientId,
				};
			},
			[ clientId ]
		);

	useEffect( () => {
		const termIdHasProducts =
			objectHasProp( filteredCounts, 'attribute_counts' ) &&
			isAttributeCounts( filteredCounts.attribute_counts )
				? filteredCounts.attribute_counts.map( ( term ) => term.term )
				: [];

		if ( termIdHasProducts.length === 0 && hideEmpty )
			return setAttributeOptions( [] );

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
	}, [ attributeTerms, filteredCounts, sortOrder, hideEmpty ] );

	useEffect( () => {
		if ( productFilterWrapperBlockId ) {
			updateBlockAttributes( productFilterWrapperBlockId, {
				heading:
					attributeObject?.label ?? __( 'Attribute', 'woocommerce' ),
				metadata: {
					name: sprintf(
						/* translators: %s is referring to the filter attribute name. For example: Color, Size, etc. */
						__( '%s (Experimental)', 'woocommerce' ),
						attributeObject?.label ??
							__( 'Attribute', 'woocommerce' )
					),
				},
			} );
		}
		if ( productFilterWrapperHeadingBlockId ) {
			updateBlockAttributes( productFilterWrapperHeadingBlockId, {
				content:
					attributeObject?.label ?? __( 'Attribute', 'woocommerce' ),
			} );
		}
	}, [
		attributeId,
		attributeObject?.id,
		attributeObject?.label,
		productFilterWrapperBlockId,
		productFilterWrapperHeadingBlockId,
		updateBlockAttributes,
	] );

	const Wrapper = ( { children }: { children: React.ReactNode } ) => (
		<div { ...useBlockProps() }>
			<Inspector { ...props } />
			{ children }
		</div>
	);

	if ( isPreview ) {
		return (
			<Wrapper>
				<Disabled>
					<AttributeCheckboxList
						showCounts={ showCounts }
						attributeTerms={ attributeOptionsPreview }
					/>
				</Disabled>
			</Wrapper>
		);
	}

	// Block rendering starts.
	if ( Object.keys( ATTRIBUTES ).length === 0 )
		return (
			<Wrapper>
				<NoAttributesPlaceholder />
			</Wrapper>
		);

	if ( ! attributeId || ! attributeObject )
		return (
			<Wrapper>
				<Notice status="warning" isDismissible={ false }>
					<p>
						{ __(
							'Please select an attribute to use this filter!',
							'woocommerce'
						) }
					</p>
				</Notice>
			</Wrapper>
		);

	if ( attributeOptions.length === 0 )
		return (
			<Wrapper>
				<Notice status="warning" isDismissible={ false }>
					<p>
						{ __(
							'There are no products with the selected attributes.',
							'woocommerce'
						) }
					</p>
				</Notice>
			</Wrapper>
		);

	return (
		<Wrapper>
			<Disabled>
				{ displayStyle === 'dropdown' ? (
					<AttributeDropdown
						label={
							attributeObject.label ||
							__( 'attribute', 'woocommerce' )
						}
					/>
				) : (
					<AttributeCheckboxList
						showCounts={ showCounts }
						attributeTerms={ attributeOptions }
					/>
				) }{ ' ' }
			</Disabled>
		</Wrapper>
	);
};

export default withSpokenMessages( Edit );
