/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	Card,
	CardBody,
	CardFooter,
	CardHeader,
	Dropdown,
	SelectControl,
} from '@wordpress/components';
import { createElement, Component, createRef } from '@wordpress/element';
import { partial, isEqual } from 'lodash';
import AddOutlineIcon from 'gridicons/dist/add-outline';
import {
	getActiveFiltersFromQuery,
	getDefaultOptionValue,
	getNewPath,
	getQueryFromActiveFilters,
	getHistory,
} from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import Link from '../link';
import AdvancedFilterItem from './item';
import { Text } from '../experimental';
import { backwardsCompatibleCreateInterpolateElement as createInterpolateElement } from './utils';
import type {
	ActiveFilter,
	AdvancedFilterAction,
	AdvancedFilterConfig,
	Currency,
	FilterChange,
	FilterConfig,
	Query,
} from './types';

export type {
	ActiveFilter,
	ActiveFilterValue,
	AdvancedFilterAction,
	AdvancedFilterConfig,
	FilterChange,
	FilterConfig,
	FilterInput,
	FilterLabels,
	FilterOption,
	FilterRule,
	Query,
} from './types';

type AdvancedFiltersDefaultProps = {
	/**
	 * The query string represented in object form.
	 */
	query: Query;
	/**
	 * Function to be called after an advanced filter action has been taken.
	 */
	onAdvancedFilterAction: (
		action: AdvancedFilterAction,
		data?: ActiveFilter | Record< string, unknown >
	) => void;
	/**
	 * The locale for the site.
	 */
	siteLocale: string;
};

export type AdvancedFiltersProps = {
	/**
	 * The configuration object required to render filters.
	 */
	config: AdvancedFilterConfig;
	/**
	 * Name of this filter, used in translations.
	 */
	path: string;
	/**
	 * The currency formatting instance for the site.
	 */
	currency: Currency;
} & Partial< AdvancedFiltersDefaultProps >;

// Inside the class the defaulted props are always present.
type Props = AdvancedFiltersProps & AdvancedFiltersDefaultProps;

type AdvancedFiltersState = {
	match: string;
	activeFilters: ActiveFilter[];
};

const matches = [
	{ value: 'all', label: __( 'All', 'woocommerce' ) },
	{ value: 'any', label: __( 'Any', 'woocommerce' ) },
];

/**
 * Displays a configurable set of filters which can modify query parameters.
 */
class AdvancedFilters extends Component< Props, AdvancedFiltersState > {
	static defaultProps: AdvancedFiltersDefaultProps = {
		query: {},
		onAdvancedFilterAction: () => {},
		siteLocale: 'en_US',
	};

	instanceCounts: Record< string, number > = {};

	filterListRef = createRef< HTMLUListElement >();

	constructor( props: Props ) {
		super( props );
		const { query, config } = props;

		const filtersFromQuery: ActiveFilter[] = getActiveFiltersFromQuery(
			query,
			config.filters
		);
		// @todo: This causes rerenders when instance numbers don't match (from adding/remove before updating query string).
		const activeFilters = filtersFromQuery.map( ( filter ) => {
			if ( config.filters[ filter.key ].allowMultiple ) {
				filter.instance = this.getInstanceNumber( filter.key );
			}

			return filter;
		} );

		this.state = {
			match:
				typeof query.match === 'string' && query.match
					? query.match
					: 'all',
			activeFilters,
		};

		this.onMatchChange = this.onMatchChange.bind( this );
		this.onFilterChange = this.onFilterChange.bind( this );
		this.getAvailableFilters = this.getAvailableFilters.bind( this );
		this.addFilter = this.addFilter.bind( this );
		this.removeFilter = this.removeFilter.bind( this );
		this.clearFilters = this.clearFilters.bind( this );
		this.getUpdateHref = this.getUpdateHref.bind( this );
		this.onFilter = this.onFilter.bind( this );
	}

	componentDidUpdate( prevProps: Props ) {
		const { config, query } = this.props;
		const { query: prevQuery } = prevProps;

		if ( ! isEqual( prevQuery, query ) ) {
			const filtersFromQuery: ActiveFilter[] = getActiveFiltersFromQuery(
				query,
				config.filters
			);

			// Update all multiple instance counts.
			this.instanceCounts = {};
			// @todo: This causes rerenders when instance numbers don't match (from adding/remove before updating query string).
			const activeFilters = filtersFromQuery.map( ( filter ) => {
				if ( config.filters[ filter.key ].allowMultiple ) {
					filter.instance = this.getInstanceNumber( filter.key );
				}

				return filter;
			} );

			this.setState( { activeFilters } );
		}
	}

	getInstanceNumber( key: string ) {
		if ( ! this.instanceCounts.hasOwnProperty( key ) ) {
			this.instanceCounts[ key ] = 1;
		}

		return this.instanceCounts[ key ]++;
	}

	onMatchChange( match: string ) {
		const { onAdvancedFilterAction } = this.props;

		this.setState( { match } );

		onAdvancedFilterAction( 'match', { match } );
	}

	onFilterChange(
		index: number,
		{ property, value, shouldResetValue = false }: FilterChange
	) {
		const newActiveFilters = [ ...this.state.activeFilters ];
		newActiveFilters[ index ] = {
			...newActiveFilters[ index ],
			[ property ]: value,
			...( shouldResetValue === true ? { value: null } : {} ),
		};

		this.setState( { activeFilters: newActiveFilters } );
	}

	removeFilter( index: number ) {
		const { onAdvancedFilterAction } = this.props;
		const activeFilters = [ ...this.state.activeFilters ];
		onAdvancedFilterAction( 'remove', activeFilters[ index ] );
		activeFilters.splice( index, 1 );
		this.setState( { activeFilters } );
		if ( activeFilters.length === 0 ) {
			const history = getHistory();
			history.push( this.getUpdateHref( [] ) );
		}
	}

	getTitle() {
		const { match } = this.state;
		const { config } = this.props;

		return createInterpolateElement( config.title, {
			select: (
				<SelectControl
					__next40pxDefaultSize
					className="woocommerce-filters-advanced__title-select"
					options={ matches }
					value={ match }
					onChange={ this.onMatchChange }
					aria-label={ __(
						'Choose to apply any or all filters',
						'woocommerce'
					) }
				/>
			),
		} );
	}

	getAvailableFilters() {
		const { config } = this.props;
		const activeFilterKeys = this.state.activeFilters.map( ( f ) => f.key );

		// Get filter objects with keys.
		const allFilters = Object.entries( config.filters ).map(
			( [ key, value ] ) => ( { key, ...value } )
		);

		// Available filters are those that allow multiple instances or are not already active.
		const availableFilters = allFilters.filter( ( filter ) => {
			return (
				filter.allowMultiple ||
				! activeFilterKeys.includes( filter.key )
			);
		} );

		// Sort filters by their add label.
		availableFilters.sort( ( a, b ) =>
			a.labels.add.localeCompare( b.labels.add )
		);

		return availableFilters;
	}

	addFilter( key: string, onClose: () => void ) {
		const { onAdvancedFilterAction, config } = this.props;
		const filterConfig: FilterConfig = config.filters[ key ];
		const newFilter: ActiveFilter = { key };
		if (
			Array.isArray( filterConfig.rules ) &&
			filterConfig.rules.length
		) {
			newFilter.rule = filterConfig.rules[ 0 ].value;
		}
		if ( filterConfig.input && filterConfig.input.options ) {
			newFilter.value = getDefaultOptionValue(
				filterConfig,
				filterConfig.input.options
			);
		}
		if ( filterConfig.input && filterConfig.input.component === 'Search' ) {
			newFilter.value = '';
		}
		if ( filterConfig.allowMultiple ) {
			newFilter.instance = this.getInstanceNumber( key );
		}
		this.setState( ( state ) => {
			return {
				activeFilters: [ ...state.activeFilters, newFilter ],
			};
		} );
		onAdvancedFilterAction( 'add', newFilter );
		onClose();
		// after render, focus the newly added filter's first focusable element
		setTimeout( () => {
			const addedFilter =
				this.filterListRef.current?.querySelector< HTMLElement >(
					'li:last-of-type fieldset'
				);
			addedFilter?.focus();
		} );
	}

	clearFilters() {
		const { onAdvancedFilterAction } = this.props;
		onAdvancedFilterAction( 'clear_all' );
		this.setState( {
			activeFilters: [],
			match: 'all',
		} );
	}

	getUpdateHref( activeFilters: ActiveFilter[], matchValue?: string ) {
		const { path, query, config } = this.props;
		const updatedQuery = getQueryFromActiveFilters(
			activeFilters,
			query,
			config.filters
		);
		const match = matchValue === 'all' ? undefined : matchValue;
		return getNewPath( { ...updatedQuery, match }, path, query );
	}

	isEnglish() {
		return /en[-|_]/.test( this.props.siteLocale );
	}

	onFilter() {
		const { onAdvancedFilterAction, query, config } = this.props;
		const { activeFilters, match } = this.state;
		const updatedQuery = getQueryFromActiveFilters(
			activeFilters,
			query,
			config.filters
		);
		onAdvancedFilterAction( 'filter', { ...updatedQuery, match } );
	}

	orderFilters( a: ActiveFilter, b: ActiveFilter ) {
		const qs = window.location.search;
		const aPos = qs.indexOf( a.key );
		const bPos = qs.indexOf( b.key );
		// If either isn't in the url, it means its just been added, so leave it as is.
		if ( aPos === -1 || bPos === -1 ) {
			return 0;
		}
		// Otherwise use the url to determine order in which filter was added.
		return aPos - bPos;
	}

	render() {
		const { config, query, currency } = this.props;
		const { activeFilters, match } = this.state;
		const availableFilters = this.getAvailableFilters();
		const updateHref = this.getUpdateHref( activeFilters, match );
		const updateDisabled =
			'admin.php' + window.location.search === updateHref ||
			activeFilters.length === 0;
		const isEnglish = this.isEnglish();
		return (
			<Card className="woocommerce-filters-advanced" size="small">
				{ /* CardHeader forwards unknown props to Flex, so `justify` works but isn't typed. */ }
				{ /* @ts-expect-error: justify is not a declared CardHeader prop. */ }
				<CardHeader justify="flex-start">
					<Text
						variant="subtitle.small"
						as="div"
						weight="600"
						size="14"
						lineHeight="20px"
						isBlock="false"
					>
						{ this.getTitle() }
					</Text>
				</CardHeader>
				{ !! activeFilters.length && (
					// An unknown size maps to no padding class, which is what the list relies on.
					// @ts-expect-error: size must be one of small, medium, large, xSmall, extraSmall.
					<CardBody size="none">
						<ul
							className="woocommerce-filters-advanced__list"
							ref={ this.filterListRef }
						>
							{ activeFilters
								.sort( this.orderFilters )
								.map( ( filter, idx ) => {
									const { instance, key } = filter;
									return (
										<AdvancedFilterItem
											key={ key + ( instance || '' ) }
											config={ config }
											currency={ currency }
											filter={ filter }
											isEnglish={ isEnglish }
											onFilterChange={ partial(
												this.onFilterChange,
												idx
											) }
											query={ query }
											removeFilter={ () =>
												this.removeFilter( idx )
											}
										/>
									);
								} ) }
						</ul>
					</CardBody>
				) }
				{ availableFilters.length > 0 && (
					<CardBody>
						<div className="woocommerce-filters-advanced__add-filter">
							<Dropdown
								className="woocommerce-filters-advanced__add-filter-dropdown"
								popoverProps={ {
									placement: 'bottom',
								} }
								renderToggle={ ( { isOpen, onToggle } ) => (
									<Button
										className="woocommerce-filters-advanced__add-button"
										onClick={ onToggle }
										aria-expanded={ isOpen }
									>
										<AddOutlineIcon />
										{ __( 'Add a filter', 'woocommerce' ) }
									</Button>
								) }
								renderContent={ ( { onClose } ) => (
									<ul className="woocommerce-filters-advanced__add-dropdown">
										{ availableFilters.map( ( filter ) => (
											<li key={ filter.key }>
												<Button
													onClick={ partial(
														this.addFilter,
														filter.key,
														onClose
													) }
												>
													{ filter.labels.add }
												</Button>
											</li>
										) ) }
									</ul>
								) }
							/>
						</div>
					</CardBody>
				) }
				{ /* CardFooter forwards unknown props to Flex, so `align` works but isn't typed. */ }
				{ /* @ts-expect-error: align is not a declared CardFooter prop. */ }
				<CardFooter align="center">
					<div className="woocommerce-filters-advanced__controls">
						{ updateDisabled && (
							<Button isPrimary disabled>
								{ __( 'Filter', 'woocommerce' ) }
							</Button>
						) }
						{ ! updateDisabled && (
							<Link
								className="components-button is-primary is-button"
								type="wc-admin"
								href={ updateHref }
								onClick={ this.onFilter }
							>
								{ __( 'Filter', 'woocommerce' ) }
							</Link>
						) }
						{ activeFilters.length > 0 && (
							<Link
								type="wc-admin"
								href={ this.getUpdateHref( [] ) }
								onClick={ this.clearFilters }
							>
								{ __( 'Clear all filters', 'woocommerce' ) }
							</Link>
						) }
					</div>
				</CardFooter>
			</Card>
		);
	}
}

export default AdvancedFilters;
