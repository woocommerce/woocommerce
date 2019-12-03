/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
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
import DropdownSelector from '@woocommerce/base-components/dropdown-selector';
import Label from '@woocommerce/base-components/label';

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
					{ name }
					{ blockAttributes.showCounts && count !== null && (
						<Label
							label={ count }
							screenReaderLabel={ sprintf(
								// translators: %s number of products.
								_n(
									'%s product',
									'%s products',
									count,
									'woo-gutenberg-products-block'
								),
								count
							) }
							wrapperElement="span"
							wrapperProps={ {
								className:
									'wc-block-attribute-filter-list-count',
							} }
						/>
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
						value: 'preview-1',
						name: 'Blue',
						label: getLabel( 'Blue', 3 ),
					},
					{
						value: 'preview-2',
						name: 'Green',
						label: getLabel( 'Green', 3 ),
					},
					{
						value: 'preview-3',
						name: 'Red',
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

	const {
		results: filteredCounts,
		isLoading: filteredCountsLoading,
	} = useCollectionData( {
		queryAttribute: {
			taxonomy: attributeObject.taxonomy,
			queryType: blockAttributes.queryType,
		},
		queryState,
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
				value: term.slug,
				name: term.name,
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
		( checkedValue ) => {
			const isChecked = ! checked.includes( checkedValue );
			const newChecked = checked.filter(
				( value ) => value !== checkedValue
			);
			const checkedOption = displayedOptions.find(
				( option ) => option.value === checkedValue
			);

			if ( isChecked ) {
				newChecked.push( checkedValue );
				newChecked.sort();
				speak(
					sprintf(
						__(
							'%s filter added.',
							'woo-gutenberg-products-block'
						),
						checkedOption.name
					)
				);
			} else {
				speak(
					sprintf(
						__(
							'%s filter removed.',
							'woo-gutenberg-products-block'
						),
						checkedOption.name
					)
				);
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
			displayedOptions,
		]
	);

	if ( displayedOptions.length === 0 && ! attributeTermsLoading ) {
		return null;
	}

	const TagName = `h${ blockAttributes.headingLevel }`;
	const isLoading = ! blockAttributes.isPreview && attributeTermsLoading;
	const isDisabled = ! blockAttributes.isPreview && filteredCountsLoading;

	return (
		<Fragment>
			{ ! isEditor && blockAttributes.heading && (
				<TagName>{ blockAttributes.heading }</TagName>
			) }
			<div className="wc-block-attribute-filter">
				{ blockAttributes.displayStyle === 'dropdown' ? (
					<DropdownSelector
						attributeLabel={ attributeObject.label }
						checked={ checked }
						className={ 'wc-block-attribute-filter-dropdown' }
						inputLabel={ blockAttributes.heading }
						isLoading={ isLoading }
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
			</div>
		</Fragment>
	);
};

export default AttributeFilterBlock;
