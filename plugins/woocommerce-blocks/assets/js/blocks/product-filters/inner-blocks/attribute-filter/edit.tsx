/**
 * External dependencies
 */
import {
	useCollection,
	useCollectionData,
} from '@woocommerce/base-context/hooks';
import { getSetting } from '@woocommerce/settings';
import {
	AttributeSetting,
	AttributeTerm,
	objectHasProp,
} from '@woocommerce/types';
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled, Notice, withSpokenMessages } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AttributeDropdown } from './components/attribute-dropdown';
import { Preview as CheckboxListPreview } from './components/checkbox-list-editor';
import { Inspector } from './components/inspector';
import { NoAttributesPlaceholder } from './components/placeholder';
import { attributeOptionsPreview } from './constants';
import './style.scss';
import { EditProps, isAttributeCounts } from './types';
import { getAttributeFromId } from './utils';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

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

	const { results: attributeTerms } = useCollection< AttributeTerm >( {
		namespace: '/wc/store/v1',
		resourceName: 'products/attributes/terms',
		resourceValues: [ attributeObject?.id || 0 ],
		shouldSelect: !! attributeObject?.id,
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
					<CheckboxListPreview
						items={ attributeOptionsPreview.map( ( term ) => {
							if ( showCounts )
								return `${ term.name } (${ term.count })`;
							return term.name;
						} ) }
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
					<CheckboxListPreview
						items={ attributeOptions.map( ( term ) => {
							if ( showCounts )
								return `${ term.name } (${ term.count })`;
							return term.name;
						} ) }
					/>
				) }
			</Disabled>
		</Wrapper>
	);
};

export default withSpokenMessages( Edit );
