/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { speak } from '@wordpress/a11y';
import { usePrevious, useShallowEqual } from '@woocommerce/base-hooks';
import {
	useQueryStateByKey,
	useQueryStateByContext,
	useCollectionData,
} from '@woocommerce/base-context/hooks';
import { getSetting, getSettingWithCoercion } from '@woocommerce/settings';
import {
	useCallback,
	useEffect,
	useState,
	useMemo,
	useRef,
} from '@wordpress/element';
import CheckboxList from '@woocommerce/base-components/checkbox-list';
import FilterSubmitButton from '@woocommerce/base-components/filter-submit-button';
import Label from '@woocommerce/base-components/filter-element-label';
import isShallowEqual from '@wordpress/is-shallow-equal';
import { decodeEntities } from '@wordpress/html-entities';
import { isBoolean, objectHasProp } from '@woocommerce/types';
import { addQueryArgs, removeQueryArgs } from '@wordpress/url';
import { PREFIX_QUERY_ARG_FILTER_TYPE } from '@woocommerce/utils';

/**
 * Internal dependencies
 */
import { previewOptions } from './preview';
import './style.scss';
import { getActiveFilters } from './utils';
import { Attributes, DisplayOption } from './types';

export const QUERY_PARAM_KEY = PREFIX_QUERY_ARG_FILTER_TYPE + 'stock_status';

/**
 * Component displaying an stock status filter.
 *
 * @param {Object}  props            Incoming props for the component.
 * @param {Object}  props.attributes Incoming block attributes.
 * @param {boolean} props.isEditor
 */
const StockStatusFilterBlock = ( {
	attributes: blockAttributes,
	isEditor = false,
}: {
	attributes: Attributes;
	isEditor?: boolean;
} ) => {
	const filteringForPhpTemplate = getSettingWithCoercion(
		'is_rendering_php_template',
		false,
		isBoolean
	);

	const [ hasSetPhpFilterDefaults, setHasSetPhpFilterDefaults ] =
		useState( false );

	const { outofstock, ...otherStockStatusOptions } = getSetting(
		'stockStatusOptions',
		{}
	);

	const STOCK_STATUS_OPTIONS = useRef(
		getSetting( 'hideOutOfStockItems', false )
			? otherStockStatusOptions
			: { outofstock, ...otherStockStatusOptions }
	);

	const [ checked, setChecked ] = useState(
		getActiveFilters( STOCK_STATUS_OPTIONS.current, QUERY_PARAM_KEY )
	);
	const [ displayedOptions, setDisplayedOptions ] = useState(
		blockAttributes.isPreview ? previewOptions : []
	);
	// Filter added to handle if there are slugs without a corresponding name defined.
	const [ initialOptions ] = useState(
		Object.entries( STOCK_STATUS_OPTIONS.current )
			.map( ( [ slug, name ] ) => ( { slug, name } ) )
			.filter( ( status ) => !! status.name )
			.sort( ( a, b ) => a.slug.localeCompare( b.slug ) )
	);

	const [ queryState ] = useQueryStateByContext();
	const [ productStockStatusQuery, setProductStockStatusQuery ] =
		useQueryStateByKey( 'stock_status', [] );

	const { results: filteredCounts, isLoading: filteredCountsLoading } =
		useCollectionData( {
			queryStock: true,
			queryState,
		} );

	/**
	 * Get count data about a given status by slug.
	 */
	const getFilteredStock = useCallback(
		( slug ) => {
			if (
				! objectHasProp( filteredCounts, 'stock_status_counts' ) ||
				! Array.isArray( filteredCounts.stock_status_counts )
			) {
				return null;
			}
			return filteredCounts.stock_status_counts.find(
				( { status, count } ) =>
					status === slug && Number( count ) !== 0
			);
		},
		[ filteredCounts ]
	);

	/**
	 * Compare intersection of all stock statuses and filtered counts to get a list of options to display.
	 */
	useEffect( () => {
		/**
		 * Checks if a status slug is in the query state.
		 *
		 * @param {string} queryStatus The status slug to check.
		 */
		const isStockStatusInQueryState = ( queryStatus: string ) => {
			if ( ! queryState?.stock_status ) {
				return false;
			}
			return queryState.stock_status.some(
				( { status = [] }: { status: string[] } ) =>
					status.includes( queryStatus )
			);
		};

		if ( filteredCountsLoading || blockAttributes.isPreview ) {
			return;
		}

		const newOptions = initialOptions
			.map( ( status ) => {
				const filteredStock = getFilteredStock( status.slug );

				if (
					! filteredStock &&
					! checked.includes( status.slug ) &&
					! isStockStatusInQueryState( status.slug )
				) {
					return null;
				}

				const count = filteredStock ? Number( filteredStock.count ) : 0;

				return {
					value: status.slug,
					name: decodeEntities( status.name ),
					label: (
						<Label
							name={ decodeEntities( status.name ) }
							count={ blockAttributes.showCounts ? count : null }
						/>
					),
				};
			} )
			.filter( ( option ): option is DisplayOption => !! option );

		setDisplayedOptions( newOptions );
	}, [
		blockAttributes.showCounts,
		blockAttributes.isPreview,
		filteredCountsLoading,
		getFilteredStock,
		checked,
		queryState.stock_status,
		initialOptions,
	] );

	/**
	 * Used to redirect the page when filters are changed so templates using the Classic Template block can filter.
	 *
	 * @param {Array} checkedOptions Array of checked stock options.
	 */
	const redirectPageForPhpTemplate = ( checkedOptions: string[] ) => {
		if ( checkedOptions.length === 0 ) {
			const url = removeQueryArgs(
				window.location.href,
				QUERY_PARAM_KEY
			);

			if ( url !== window.location.href ) {
				window.location.href = url;
			}
			return;
		}

		const newUrl = addQueryArgs( window.location.href, {
			[ QUERY_PARAM_KEY ]: checkedOptions.join( ',' ),
		} );

		if ( newUrl !== window.location.href ) {
			window.location.href = newUrl;
		}
	};

	const onSubmit = useCallback(
		( isChecked ) => {
			if ( isEditor ) {
				return;
			}
			if ( isChecked ) {
				setProductStockStatusQuery( checked );
			}
			// For PHP templates when the filter button is enabled.
			if ( filteringForPhpTemplate ) {
				redirectPageForPhpTemplate( checked );
			}
		},
		[
			isEditor,
			setProductStockStatusQuery,
			checked,
			filteringForPhpTemplate,
		]
	);

	// Track checked STATE changes - if state changes, update the query.
	useEffect( () => {
		if ( ! blockAttributes.showFilterButton ) {
			onSubmit( checked );
		}
	}, [ blockAttributes.showFilterButton, checked, onSubmit ] );

	const checkedQuery = useMemo( () => {
		return productStockStatusQuery;
	}, [ productStockStatusQuery ] );

	const currentCheckedQuery = useShallowEqual( checkedQuery );
	const previousCheckedQuery = usePrevious( currentCheckedQuery );
	// Track Stock query changes so the block reflects current filters.
	useEffect( () => {
		if (
			! isShallowEqual( previousCheckedQuery, currentCheckedQuery ) && // Checked query changed.
			! isShallowEqual( checked, currentCheckedQuery ) // Checked query doesn't match the UI.
		) {
			setChecked( currentCheckedQuery );
		}
	}, [ checked, currentCheckedQuery, previousCheckedQuery ] );

	/**
	 * Important: For PHP rendered block templates only.
	 */
	useEffect( () => {
		if ( filteringForPhpTemplate ) {
			setChecked( checked );
		}
	}, [ filteringForPhpTemplate, checked ] );

	/**
	 * Important: For PHP rendered block templates only.
	 */
	useEffect( () => {
		if ( ! hasSetPhpFilterDefaults && filteringForPhpTemplate ) {
			setProductStockStatusQuery(
				getActiveFilters(
					STOCK_STATUS_OPTIONS.current,
					QUERY_PARAM_KEY
				)
			);
			setHasSetPhpFilterDefaults( true );
		}
	}, [
		filteringForPhpTemplate,
		setProductStockStatusQuery,
		hasSetPhpFilterDefaults,
		setHasSetPhpFilterDefaults,
	] );

	/**
	 * When a checkbox in the list changes, update state.
	 */
	const onChange = useCallback(
		( checkedValue ) => {
			const getFilterNameFromValue = ( filterValue: string ) => {
				const filterOption = displayedOptions.find(
					( option ) => option.value === filterValue
				);

				if ( ! filterOption ) {
					return null;
				}

				return filterOption.name;
			};

			const announceFilterChange = ( {
				filterAdded,
				filterRemoved,
			}: Record< string, string > ) => {
				const filterAddedName = filterAdded
					? getFilterNameFromValue( filterAdded )
					: null;
				const filterRemovedName = filterRemoved
					? getFilterNameFromValue( filterRemoved )
					: null;
				if ( filterAddedName ) {
					speak(
						sprintf(
							/* translators: %s stock statuses (for example: 'instock'...) */
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
							/* translators: %s stock statuses (for example:'instock'...) */
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

			const newChecked = checked.filter(
				( value ) => value !== checkedValue
			);

			if ( ! previouslyChecked ) {
				newChecked.push( checkedValue );
				newChecked.sort();
				announceFilterChange( { filterAdded: checkedValue } );
			} else {
				announceFilterChange( { filterRemoved: checkedValue } );
			}

			setChecked( newChecked );
		},
		[ checked, displayedOptions ]
	);

	if ( displayedOptions.length === 0 ) {
		return null;
	}

	const TagName =
		`h${ blockAttributes.headingLevel }` as keyof JSX.IntrinsicElements;
	const isLoading =
		! blockAttributes.isPreview && ! STOCK_STATUS_OPTIONS.current;
	const isDisabled = ! blockAttributes.isPreview && filteredCountsLoading;

	const hasFilterableProducts = getSettingWithCoercion(
		'has_filterable_products',
		false,
		isBoolean
	);

	if ( ! hasFilterableProducts ) {
		return null;
	}

	return (
		<>
			{ ! isEditor && blockAttributes.heading && (
				<TagName className="wc-block-stock-filter__title">
					{ blockAttributes.heading }
				</TagName>
			) }
			<div className="wc-block-stock-filter">
				<CheckboxList
					className={ 'wc-block-stock-filter-list' }
					options={ displayedOptions }
					checked={ checked }
					onChange={ onChange }
					isLoading={ isLoading }
					isDisabled={ isDisabled }
				/>
				{ blockAttributes.showFilterButton && (
					<FilterSubmitButton
						className="wc-block-stock-filter__button"
						disabled={ isLoading || isDisabled }
						onClick={ () => onSubmit( checked ) }
					/>
				) }
			</div>
		</>
	);
};

export default StockStatusFilterBlock;
