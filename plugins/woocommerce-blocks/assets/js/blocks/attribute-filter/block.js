/**
 * External dependencies
 */
import {
	useCollection,
	useQueryStateByKey,
	useQueryStateByContext,
} from '@woocommerce/base-hooks';
import {
	useCallback,
	Fragment,
	useEffect,
	useState,
	useMemo,
} from '@wordpress/element';
import CheckboxList from '@woocommerce/base-components/checkbox-list';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';

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
	isPreview = false,
} ) => {
	const [ displayedOptions, setDisplayedOptions ] = useState( [] );
	const attributeObject = getAttributeFromID( blockAttributes.attributeId );
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

	const filteredCountsQueryState = useMemo( () => {
		// If doing an "AND" query, we need to remove current taxonomy query so counts are not affected.
		const modifiedQueryState =
			blockAttributes.queryType === 'or'
				? productAttributesQuery.filter(
						( item ) => item.attribute !== attributeObject.taxonomy
				  )
				: productAttributesQuery;

		// Take current query and remove paging args.
		return {
			...queryState,
			orderby: undefined,
			order: undefined,
			per_page: undefined,
			page: undefined,
			attributes: modifiedQueryState,
			calculate_attribute_counts: [ attributeObject.taxonomy ],
		};
	}, [
		queryState,
		attributeObject,
		blockAttributes,
		productAttributesQuery,
	] );

	const {
		results: attributeTerms,
		isLoading: attributeTermsLoading,
	} = useCollection( {
		namespace: '/wc/store',
		resourceName: 'products/attributes/terms',
		resourceValues: [ attributeObject.id ],
	} );

	const {
		results: filteredCounts,
		isLoading: filteredCountsLoading,
	} = useCollection( {
		namespace: '/wc/store',
		resourceName: 'products/collection-data',
		query: filteredCountsQueryState,
	} );

	/**
	 * Get the label for an attribute term filter.
	 */
	const getLabel = useCallback(
		( name, count ) => {
			return (
				<Fragment key="label">
					{ name }
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

	if (
		! attributeObject ||
		( displayedOptions.length === 0 && ! attributeTermsLoading )
	) {
		return null;
	}

	const TagName = `h${ blockAttributes.headingLevel }`;

	return (
		<BlockErrorBoundary>
			{ ! isPreview && blockAttributes.heading && (
				<TagName>{ blockAttributes.heading }</TagName>
			) }
			<div className="wc-block-attribute-filter">
				<CheckboxList
					className={ 'wc-block-attribute-filter-list' }
					options={ displayedOptions }
					checked={ checked }
					onChange={ onChange }
					isLoading={ attributeTermsLoading }
					isDisabled={ filteredCountsLoading }
				/>
			</div>
		</BlockErrorBoundary>
	);
};

export default AttributeFilterBlock;
