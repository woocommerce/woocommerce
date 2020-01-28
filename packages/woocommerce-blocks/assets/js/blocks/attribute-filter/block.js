/**
 * External dependencies
 */
import {
	useCollection,
	useQueryStateByKey,
	useQueryStateByContext,
	useCollectionData,
} from '@woocommerce/base-hooks';
import {
	useCallback,
	Fragment,
	useEffect,
	useState,
	useMemo,
} from '@wordpress/element';
import CheckboxList from '@woocommerce/base-components/checkbox-list';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import './style.scss';
import { getAttributeFromID } from '../../utils/attributes';
import { updateAttributeFilter } from '../../utils/attributes-query';

/**
 * Component displaying an attribute filter.
 */
const AttributeFilterBlock = ( {
	attributes: blockAttributes,
	isEditor = false,
} ) => {
	/**
	 * Get the label for an attribute term filter.
	 */
	const getLabel = useCallback(
		( name, count ) => {
			return (
				<Fragment>
					{ decodeEntities( name ) }
					{ blockAttributes.showCounts && count !== null && (
						<span className="wc-block-attribute-filter-list-count">
							{ count }
						</span>
					) }
				</Fragment>
			);
		},
		[ blockAttributes ]
	);

	const attributeObject =
		blockAttributes.isPreview && ! blockAttributes.attributeId
			? {
					id: 0,
					name: 'preview',
					taxonomy: 'preview',
					label: 'Preview',
			  }
			: getAttributeFromID( blockAttributes.attributeId );
	const [ displayedOptions, setDisplayedOptions ] = useState(
		blockAttributes.isPreview && ! blockAttributes.attributeId
			? [
					{
						key: 'preview-1',
						label: getLabel( 'Blue', 3 ),
					},
					{
						key: 'preview-2',
						label: getLabel( 'Green', 3 ),
					},
					{
						key: 'preview-3',
						label: getLabel( 'Red', 2 ),
					},
			  ]
			: []
	);

	const [ queryState ] = useQueryStateByContext();
	const [
		productAttributesQuery,
		setProductAttributesQuery,
	] = useQueryStateByKey( 'attributes', [] );

	const checked = useMemo( () => {
		return productAttributesQuery
			.filter(
				( attribute ) =>
					attribute.attribute === attributeObject.taxonomy
			)
			.flatMap( ( attribute ) => attribute.slug );
	}, [ productAttributesQuery, attributeObject ] );

	const {
		results: attributeTerms,
		isLoading: attributeTermsLoading,
	} = useCollection( {
		namespace: '/wc/store',
		resourceName: 'products/attributes/terms',
		resourceValues: [ attributeObject.id ],
		shouldSelect: blockAttributes.attributeId > 0,
	} );

	const filterAvailableTerms =
		blockAttributes.displayStyle !== 'dropdown' &&
		blockAttributes.queryType === 'and';
	const {
		results: filteredCounts,
		isLoading: filteredCountsLoading,
	} = useCollectionData( {
		queryAttribute: {
			taxonomy: attributeObject.taxonomy,
			queryType: blockAttributes.queryType,
		},
		queryState: {
			...queryState,
			attributes: filterAvailableTerms ? queryState.attributes : null,
		},
	} );

	/**
	 * Get count data about a given term by ID.
	 */
	const getFilteredTerm = useCallback(
		( id ) => {
			if ( ! filteredCounts.attribute_counts ) {
				return null;
			}
			return filteredCounts.attribute_counts.find(
				( { term } ) => term === id
			);
		},
		[ filteredCounts ]
	);

	/**
	 * Compare intersection of all terms and filtered counts to get a list of options to display.
	 */
	useEffect( () => {
		if ( attributeTermsLoading || filteredCountsLoading ) {
			return;
		}

		const newOptions = [];

		attributeTerms.forEach( ( term ) => {
			const filteredTerm = getFilteredTerm( term.id );
			const isChecked = checked.includes( term.slug );
			const count = filteredTerm ? filteredTerm.count : null;

			// If there is no match this term doesn't match the current product collection - only render if checked.
			if ( ! filteredTerm && ! isChecked ) {
				return;
			}

			newOptions.push( {
				key: term.slug,
				label: getLabel( term.name, count ),
			} );
		} );

		setDisplayedOptions( newOptions );
	}, [
		attributeTerms,
		attributeTermsLoading,
		filteredCountsLoading,
		getFilteredTerm,
		getLabel,
		checked,
	] );

	/**
	 * Returns an array of term objects that have been chosen via the checkboxes.
	 */
	const getSelectedTerms = useCallback(
		( newChecked ) => {
			return attributeTerms.reduce( ( acc, term ) => {
				if ( newChecked.includes( term.slug ) ) {
					acc.push( term );
				}
				return acc;
			}, [] );
		},
		[ attributeTerms ]
	);

	/**
	 * When a checkbox in the list changes, update state.
	 */
	const onChange = useCallback(
		( event ) => {
			const isChecked = event.target.checked;
			const checkedValue = event.target.value;
			const newChecked = checked.filter(
				( value ) => value !== checkedValue
			);

			if ( isChecked ) {
				newChecked.push( checkedValue );
				newChecked.sort();
			}

			const newSelectedTerms = getSelectedTerms( newChecked );

			updateAttributeFilter(
				productAttributesQuery,
				setProductAttributesQuery,
				attributeObject,
				newSelectedTerms,
				blockAttributes.queryType === 'or' ? 'in' : 'and'
			);
		},
		[
			attributeTerms,
			checked,
			productAttributesQuery,
			setProductAttributesQuery,
			attributeObject,
			blockAttributes,
		]
	);

	if ( displayedOptions.length === 0 && ! attributeTermsLoading ) {
		return null;
	}

	const TagName = `h${ blockAttributes.headingLevel }`;

	return (
		<Fragment>
			{ ! isEditor && blockAttributes.heading && (
				<TagName>{ blockAttributes.heading }</TagName>
			) }
			<div className="wc-block-attribute-filter">
				<CheckboxList
					className={ 'wc-block-attribute-filter-list' }
					options={ displayedOptions }
					checked={ checked }
					onChange={ onChange }
					isLoading={
						! blockAttributes.isPreview && attributeTermsLoading
					}
					isDisabled={
						! blockAttributes.isPreview && filteredCountsLoading
					}
				/>
			</div>
		</Fragment>
	);
};

export default AttributeFilterBlock;
