/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { usePrevious, useShallowEqual } from '@woocommerce/base-hooks';
import {
	useCollection,
	useQueryStateByKey,
	useQueryStateByContext,
	useCollectionData,
} from '@woocommerce/base-context/hooks';
import { useCallback, useEffect, useState, useMemo } from '@wordpress/element';
import CheckboxList from '@woocommerce/base-components/checkbox-list';
import DropdownSelector from '@woocommerce/base-components/dropdown-selector';
import Label from '@woocommerce/base-components/filter-element-label';
import FilterSubmitButton from '@woocommerce/base-components/filter-submit-button';
import isShallowEqual from '@wordpress/is-shallow-equal';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { getAttributeFromID } from '../../utils/attributes';
import { updateAttributeFilter } from '../../utils/attributes-query';
import { previewAttributeObject, previewOptions } from './preview';
import './style.scss';

/**
 * Component displaying an attribute filter.
 *
 * @param {Object} props Incoming props for the component.
 * @param {Object} props.attributes Incoming block attributes.
 * @param {boolean} props.isEditor
 */
const AttributeFilterBlock = ( {
	attributes: blockAttributes,
	isEditor = false,
} ) => {
	const attributeObject =
		blockAttributes.isPreview && ! blockAttributes.attributeId
			? previewAttributeObject
			: getAttributeFromID( blockAttributes.attributeId );

	const [ checked, setChecked ] = useState( [] );
	const [ displayedOptions, setDisplayedOptions ] = useState(
		blockAttributes.isPreview && ! blockAttributes.attributeId
			? previewOptions
			: []
	);

	const [ queryState ] = useQueryStateByContext();
	const [
		productAttributesQuery,
		setProductAttributesQuery,
	] = useQueryStateByKey( 'attributes', [] );

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
		/**
		 * Checks if a term slug is in the query state.
		 *
		 * @param {string} termSlug The term of the slug to check.
		 */
		const isTermInQueryState = ( termSlug ) => {
			if ( ! queryState?.attributes ) {
				return false;
			}
			return queryState.attributes.some(
				( { attribute, slug = [] } ) =>
					attribute === attributeObject.taxonomy &&
					slug.includes( termSlug )
			);
		};

		if ( attributeTermsLoading || filteredCountsLoading ) {
			return;
		}

		const newOptions = attributeTerms
			.map( ( term ) => {
				const filteredTerm = getFilteredTerm( term.id );

				// If there is no match this term doesn't match the current product collection - only render if checked.
				if (
					! filteredTerm &&
					! checked.includes( term.slug ) &&
					! isTermInQueryState( term.slug )
				) {
					return null;
				}

				const count = filteredTerm ? filteredTerm.count : 0;

				return {
					value: term.slug,
					name: decodeEntities( term.name ),
					label: (
						<Label
							name={ decodeEntities( term.name ) }
							count={ blockAttributes.showCounts ? count : null }
						/>
					),
				};
			} )
			.filter( Boolean );

		setDisplayedOptions( newOptions );
	}, [
		attributeObject.taxonomy,
		attributeTerms,
		attributeTermsLoading,
		blockAttributes.showCounts,
		filteredCountsLoading,
		getFilteredTerm,
		checked,
		queryState.attributes,
	] );

	const checkedQuery = useMemo( () => {
		return productAttributesQuery
			.filter(
				( { attribute } ) => attribute === attributeObject.taxonomy
			)
			.flatMap( ( { slug } ) => slug );
	}, [ productAttributesQuery, attributeObject.taxonomy ] );

	const currentCheckedQuery = useShallowEqual( checkedQuery );
	const previousCheckedQuery = usePrevious( currentCheckedQuery );
	// Track ATTRIBUTES QUERY changes so the block reflects current filters.
	useEffect( () => {
		if (
			! isShallowEqual( previousCheckedQuery, currentCheckedQuery ) && // checked query changed
			! isShallowEqual( checked, currentCheckedQuery ) // checked query doesn't match the UI
		) {
			setChecked( currentCheckedQuery );
			if ( ! blockAttributes.showFilterButton ) {
				onSubmit( currentCheckedQuery );
			}
		}
	}, [
		checked,
		currentCheckedQuery,
		previousCheckedQuery,
		onSubmit,
		blockAttributes.showFilterButton,
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

	const onSubmit = useCallback(
		( isChecked ) => {
			if ( isEditor ) {
				return;
			}

			updateAttributeFilter(
				productAttributesQuery,
				setProductAttributesQuery,
				attributeObject,
				getSelectedTerms( isChecked ),
				blockAttributes.queryType === 'or' ? 'in' : 'and'
			);
		},
		[
			isEditor,
			productAttributesQuery,
			setProductAttributesQuery,
			attributeObject,
			getSelectedTerms,
			blockAttributes.queryType,
		]
	);

	const multiple =
		blockAttributes.displayStyle !== 'dropdown' ||
		blockAttributes.queryType === 'or';

	/**
	 * When a checkbox in the list changes, update state.
	 */
	const onChange = useCallback(
		( checkedValue ) => {
			const getFilterNameFromValue = ( filterValue ) => {
				const { name } = displayedOptions.find(
					( option ) => option.value === filterValue
				);

				return name;
			};

			const announceFilterChange = ( { filterAdded, filterRemoved } ) => {
				const filterAddedName = filterAdded
					? getFilterNameFromValue( filterAdded )
					: null;
				const filterRemovedName = filterRemoved
					? getFilterNameFromValue( filterRemoved )
					: null;
				if ( filterAddedName && filterRemovedName ) {
					speak(
						sprintf(
							/* translators: %1$s and %2$s are attribute terms (for example: 'red', 'blue', 'large'...). */
							__(
								'%1$s filter replaced with %2$s.',
								'woocommerce'
							),
							filterAddedName,
							filterRemovedName
						)
					);
				} else if ( filterAddedName ) {
					speak(
						sprintf(
							/* translators: %s attribute term (for example: 'red', 'blue', 'large'...) */
							__(
								'%s filter added.',
								'woocommerce'
							),
							filterAddedName
						)
					);
				} else if ( filterRemovedName ) {
					speak(
						sprintf(
							/* translators: %s attribute term (for example: 'red', 'blue', 'large'...) */
							__(
								'%s filter removed.',
								'woocommerce'
							),
							filterRemovedName
						)
					);
				}
			};

			const previouslyChecked = checked.includes( checkedValue );
			let newChecked;

			if ( ! multiple ) {
				newChecked = previouslyChecked ? [] : [ checkedValue ];
				const filterAdded = previouslyChecked ? null : checkedValue;
				const filterRemoved =
					checked.length === 1 ? checked[ 0 ] : null;
				announceFilterChange( { filterAdded, filterRemoved } );
			} else {
				newChecked = checked.filter(
					( value ) => value !== checkedValue
				);

				if ( ! previouslyChecked ) {
					newChecked.push( checkedValue );
					newChecked.sort();
					announceFilterChange( { filterAdded: checkedValue } );
				} else {
					announceFilterChange( { filterRemoved: checkedValue } );
				}
			}

			setChecked( newChecked );
			if ( ! blockAttributes.showFilterButton ) {
				onSubmit( newChecked );
			}
		},
		[
			checked,
			displayedOptions,
			multiple,
			onSubmit,
			blockAttributes.showFilterButton,
		]
	);

	if ( displayedOptions.length === 0 && ! attributeTermsLoading ) {
		return null;
	}

	const TagName = `h${ blockAttributes.headingLevel }`;
	const isLoading = ! blockAttributes.isPreview && attributeTermsLoading;
	const isDisabled = ! blockAttributes.isPreview && filteredCountsLoading;

	return (
		<>
			{ ! isEditor && blockAttributes.heading && (
				<TagName className="wc-block-attribute-filter__title">
					{ blockAttributes.heading }
				</TagName>
			) }
			<div
				className={ `wc-block-attribute-filter style-${ blockAttributes.displayStyle }` }
			>
				{ blockAttributes.displayStyle === 'dropdown' ? (
					<DropdownSelector
						attributeLabel={ attributeObject.label }
						checked={ checked }
						className={ 'wc-block-attribute-filter-dropdown' }
						inputLabel={ blockAttributes.heading }
						isLoading={ isLoading }
						multiple={ multiple }
						onChange={ onChange }
						options={ displayedOptions }
					/>
				) : (
					<CheckboxList
						className={ 'wc-block-attribute-filter-list' }
						options={ displayedOptions }
						checked={ checked }
						onChange={ onChange }
						isLoading={ isLoading }
						isDisabled={ isDisabled }
					/>
				) }
				{ blockAttributes.showFilterButton && (
					<FilterSubmitButton
						className="wc-block-attribute-filter__button"
						disabled={ isLoading || isDisabled }
						onClick={ () => onSubmit( checked ) }
					/>
				) }
			</div>
		</>
	);
};

export default AttributeFilterBlock;
