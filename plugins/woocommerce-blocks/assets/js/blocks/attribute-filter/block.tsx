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
import { Notice } from 'wordpress-components';
import classNames from 'classnames';
import { getSettingWithCoercion } from '@woocommerce/settings';
import { getQueryArgs, removeQueryArgs } from '@wordpress/url';
import {
	AttributeQuery,
	isAttributeQueryCollection,
	isBoolean,
	isString,
	objectHasProp,
} from '@woocommerce/types';
import {
	PREFIX_QUERY_ARG_FILTER_TYPE,
	PREFIX_QUERY_ARG_QUERY_TYPE,
} from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import { getAttributeFromID } from '../../utils/attributes';
import { updateAttributeFilter } from '../../utils/attributes-query';
import { previewAttributeObject, previewOptions } from './preview';
import { useBorderProps } from '../../hooks/style-attributes';
import './style.scss';
import {
	formatParams,
	getActiveFilters,
	areAllFiltersRemoved,
	isQueryArgsEqual,
	parseTaxonomyToGenerateURL,
} from './utils';
import { BlockAttributes, DisplayOption } from './types';

/**
 * Formats filter values into a string for the URL parameters needed for filtering PHP templates.
 *
 * @param {string} url    Current page URL.
 * @param {Array}  params Parameters and their constraints.
 *
 * @return {string}       New URL with query parameters in it.
 */

/**
 * Component displaying an attribute filter.
 *
 * @param {Object}  props            Incoming props for the component.
 * @param {Object}  props.attributes Incoming block attributes.
 * @param {boolean} props.isEditor
 */
const AttributeFilterBlock = ( {
	attributes: blockAttributes,
	isEditor = false,
}: {
	attributes: BlockAttributes;
	isEditor?: boolean;
} ) => {
	const hasFilterableProducts = getSettingWithCoercion(
		'has_filterable_products',
		false,
		isBoolean
	);

	const filteringForPhpTemplate = getSettingWithCoercion(
		'is_rendering_php_template',
		false,
		isBoolean
	);

	const pageUrl = getSettingWithCoercion(
		'page_url',
		window.location.href,
		isString
	);

	const [ hasSetPhpFilterDefaults, setHasSetPhpFilterDefaults ] =
		useState( false );

	const attributeObject =
		blockAttributes.isPreview && ! blockAttributes.attributeId
			? previewAttributeObject
			: getAttributeFromID( blockAttributes.attributeId );

	const [ checked, setChecked ] = useState(
		getActiveFilters( filteringForPhpTemplate, attributeObject )
	);

	const [ displayedOptions, setDisplayedOptions ] = useState<
		DisplayOption[]
	>(
		blockAttributes.isPreview && ! blockAttributes.attributeId
			? previewOptions
			: []
	);

	const borderProps = useBorderProps( blockAttributes );

	const [ queryState ] = useQueryStateByContext();
	const [ productAttributesQuery, setProductAttributesQuery ] =
		useQueryStateByKey( 'attributes', [] );

	const { results: attributeTerms, isLoading: attributeTermsLoading } =
		useCollection( {
			namespace: '/wc/store/v1',
			resourceName: 'products/attributes/terms',
			resourceValues: [ attributeObject?.id || 0 ],
			shouldSelect: blockAttributes.attributeId > 0,
		} );

	const filterAvailableTerms =
		blockAttributes.displayStyle !== 'dropdown' &&
		blockAttributes.queryType === 'and';
	const { results: filteredCounts, isLoading: filteredCountsLoading } =
		useCollectionData( {
			queryAttribute: {
				taxonomy: attributeObject?.taxonomy || '',
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
			if (
				! objectHasProp( filteredCounts, 'attribute_counts' ) ||
				! Array.isArray( filteredCounts.attribute_counts )
			) {
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
		const isTermInQueryState = ( termSlug: string ) => {
			if ( ! queryState?.attributes ) {
				return false;
			}
			return queryState.attributes.some(
				( { attribute, slug = [] }: AttributeQuery ) =>
					attribute === attributeObject?.taxonomy &&
					slug.includes( termSlug )
			);
		};

		if ( attributeTermsLoading || filteredCountsLoading ) {
			return;
		}

		if ( ! Array.isArray( attributeTerms ) ) {
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
			.filter( ( option ): option is DisplayOption => !! option );

		setDisplayedOptions( newOptions );
	}, [
		attributeObject?.taxonomy,
		attributeTerms,
		attributeTermsLoading,
		blockAttributes.showCounts,
		filteredCountsLoading,
		getFilteredTerm,
		checked,
		queryState.attributes,
	] );

	/**
	 * Returns an array of term objects that have been chosen via the checkboxes.
	 */
	const getSelectedTerms = useCallback(
		( newChecked ) => {
			if ( ! Array.isArray( attributeTerms ) ) {
				return [];
			}
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
	 * Appends query params to the current pages URL and redirects them to the new URL for PHP rendered templates.
	 *
	 * @param {Object}  query             The object containing the active filter query.
	 * @param {boolean} allFiltersRemoved If there are active filters or not.
	 */
	const redirectPageForPhpTemplate = useCallback(
		( query, allFiltersRemoved = false ) => {
			if ( allFiltersRemoved ) {
				if ( ! attributeObject?.taxonomy ) {
					return;
				}
				const currentQueryArgKeys = Object.keys(
					getQueryArgs( window.location.href )
				);

				const parsedTaxonomy = parseTaxonomyToGenerateURL(
					attributeObject.taxonomy
				);

				const url = currentQueryArgKeys.reduce(
					( currentUrl, queryArg ) =>
						queryArg.includes(
							PREFIX_QUERY_ARG_QUERY_TYPE + parsedTaxonomy
						) ||
						queryArg.includes(
							PREFIX_QUERY_ARG_FILTER_TYPE + parsedTaxonomy
						)
							? removeQueryArgs( currentUrl, queryArg )
							: currentUrl,
					window.location.href
				);

				const newUrl = formatParams( url, query );
				window.location.href = newUrl;
			} else {
				const newUrl = formatParams( pageUrl, query );
				const currentQueryArgs = getQueryArgs( window.location.href );
				const newUrlQueryArgs = getQueryArgs( newUrl );

				if ( ! isQueryArgsEqual( currentQueryArgs, newUrlQueryArgs ) ) {
					window.location.href = newUrl;
				}
			}
		},
		[ pageUrl, attributeObject?.taxonomy ]
	);

	const onSubmit = ( checkedFilters: string[] ) => {
		const query = updateAttributeFilter(
			productAttributesQuery,
			setProductAttributesQuery,
			attributeObject,
			getSelectedTerms( checkedFilters ),
			blockAttributes.queryType === 'or' ? 'in' : 'and'
		);

		// This is for PHP rendered template filtering only.
		if ( filteringForPhpTemplate ) {
			redirectPageForPhpTemplate( query, checkedFilters.length === 0 );
		}
	};

	const updateCheckedFilters = useCallback(
		( checkedFilters: string[] ) => {
			if ( isEditor ) {
				return;
			}

			setChecked( checkedFilters );
			if ( ! blockAttributes.showFilterButton ) {
				updateAttributeFilter(
					productAttributesQuery,
					setProductAttributesQuery,
					attributeObject,
					getSelectedTerms( checkedFilters ),
					blockAttributes.queryType === 'or' ? 'in' : 'and'
				);
			}
		},
		[
			isEditor,
			setChecked,
			productAttributesQuery,
			setProductAttributesQuery,
			attributeObject,
			getSelectedTerms,
			blockAttributes.queryType,
			blockAttributes.showFilterButton,
		]
	);

	const checkedQuery = useMemo( () => {
		if ( ! isAttributeQueryCollection( productAttributesQuery ) ) {
			return [];
		}

		return productAttributesQuery
			.filter(
				( { attribute } ) => attribute === attributeObject?.taxonomy
			)
			.flatMap( ( { slug } ) => slug );
	}, [ productAttributesQuery, attributeObject?.taxonomy ] );

	const currentCheckedQuery = useShallowEqual( checkedQuery );
	const previousCheckedQuery = usePrevious( currentCheckedQuery );
	// Track ATTRIBUTES QUERY changes so the block reflects current filters.
	useEffect( () => {
		if (
			previousCheckedQuery &&
			! isShallowEqual( previousCheckedQuery, currentCheckedQuery ) && // checked query changed
			! isShallowEqual( checked, currentCheckedQuery ) // checked query doesn't match the UI
		) {
			updateCheckedFilters( currentCheckedQuery );
		}
	}, [
		checked,
		currentCheckedQuery,
		previousCheckedQuery,
		updateCheckedFilters,
	] );

	const multiple =
		blockAttributes.displayStyle !== 'dropdown' ||
		blockAttributes.queryType === 'or';

	/**
	 * When a checkbox in the list changes, update state.
	 */
	const onChange = useCallback(
		( checkedValue ) => {
			const getFilterNameFromValue = ( filterValue: string ) => {
				const result = displayedOptions.find(
					( option ) => option.value === filterValue
				);

				if ( result ) {
					return result.name;
				}
			};

			const announceFilterChange = ( {
				filterAdded,
				filterRemoved,
			}: {
				filterAdded?: string | null;
				filterRemoved?: string | null;
			} ) => {
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
								'woo-gutenberg-products-block'
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
								'woo-gutenberg-products-block'
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
								'woo-gutenberg-products-block'
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

			updateCheckedFilters( newChecked );
		},
		[ checked, displayedOptions, multiple, updateCheckedFilters ]
	);

	/**
	 * Important: For PHP rendered block templates only.
	 *
	 * When we render the PHP block template (e.g. Classic Block) we need to set the default checked values,
	 * and also update the URL when the filters are clicked/updated.
	 */
	useEffect( () => {
		if ( filteringForPhpTemplate && attributeObject ) {
			if (
				areAllFiltersRemoved( {
					currentCheckedFilters: checked,
					hasSetPhpFilterDefaults,
				} )
			) {
				if ( ! blockAttributes.showFilterButton ) {
					setChecked( [] );
					redirectPageForPhpTemplate( productAttributesQuery, true );
				}
			}

			if ( ! blockAttributes.showFilterButton ) {
				setChecked( checked );
				redirectPageForPhpTemplate( productAttributesQuery, false );
			}
		}
	}, [
		hasSetPhpFilterDefaults,
		redirectPageForPhpTemplate,
		filteringForPhpTemplate,
		productAttributesQuery,
		attributeObject,
		checked,
		blockAttributes.showFilterButton,
	] );

	/**
	 * Important: For PHP rendered block templates only.
	 *
	 * When we set the default parameter values which we get from the URL in the above useEffect(),
	 * we need to run updateCheckedFilters which will set these values in state for the Active Filters block.
	 */
	useEffect( () => {
		if ( filteringForPhpTemplate ) {
			const activeFilters = getActiveFilters(
				filteringForPhpTemplate,
				attributeObject
			);
			if (
				activeFilters.length > 0 &&
				! hasSetPhpFilterDefaults &&
				! attributeTermsLoading
			) {
				setHasSetPhpFilterDefaults( true );
				updateCheckedFilters( activeFilters );
			}
		}
	}, [
		attributeObject,
		filteringForPhpTemplate,
		hasSetPhpFilterDefaults,
		attributeTermsLoading,
		updateCheckedFilters,
	] );

	if ( ! hasFilterableProducts ) {
		return null;
	}

	// Short-circuit if no attribute is selected.
	if ( ! attributeObject ) {
		if ( isEditor ) {
			return (
				<Notice status="warning" isDismissible={ false }>
					<p>
						{ __(
							'Please select an attribute to use this filter!',
							'woo-gutenberg-products-block'
						) }
					</p>
				</Notice>
			);
		}
		return null;
	}

	if ( displayedOptions.length === 0 && ! attributeTermsLoading ) {
		if ( isEditor ) {
			return (
				<Notice status="warning" isDismissible={ false }>
					<p>
						{ __(
							'The selected attribute does not have any term assigned to products.',
							'woo-gutenberg-products-block'
						) }
					</p>
				</Notice>
			);
		}
		return null;
	}

	const TagName =
		`h${ blockAttributes.headingLevel }` as keyof JSX.IntrinsicElements;
	const isLoading = ! blockAttributes.isPreview && attributeTermsLoading;
	const isDisabled = ! blockAttributes.isPreview && filteredCountsLoading;

	return (
		<>
			{ ! isEditor &&
				blockAttributes.heading &&
				displayedOptions.length > 0 && (
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
						className={ classNames(
							'wc-block-attribute-filter-dropdown',
							borderProps.className
						) }
						style={ { ...borderProps.style, borderStyle: 'none' } }
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
