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
import { partial, difference, isEqual } from 'lodash';
import PropTypes from 'prop-types';
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

const matches = [
	{ value: 'all', label: __( 'All', 'woocommerce' ) },
	{ value: 'any', label: __( 'Any', 'woocommerce' ) },
];

/**
 * Displays a configurable set of filters which can modify query parameters.
 */
class AdvancedFilters extends Component {
	constructor( { query, config } ) {
		super( ...arguments );
		this.instanceCounts = {};

		const filtersFromQuery = getActiveFiltersFromQuery(
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
			match: query.match || 'all',
			activeFilters,
		};

		this.filterListRef = createRef();

		this.onMatchChange = this.onMatchChange.bind( this );
		this.onFilterChange = this.onFilterChange.bind( this );
		this.getAvailableFilters = this.getAvailableFilters.bind( this );
		this.addFilter = this.addFilter.bind( this );
		this.removeFilter = this.removeFilter.bind( this );
		this.clearFilters = this.clearFilters.bind( this );
		this.getUpdateHref = this.getUpdateHref.bind( this );
		this.onFilter = this.onFilter.bind( this );
	}

	componentDidUpdate( prevProps ) {
		const { config, query } = this.props;
		const { query: prevQuery } = prevProps;

		if ( ! isEqual( prevQuery, query ) ) {
			const filtersFromQuery = getActiveFiltersFromQuery(
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

			/* eslint-disable react/no-did-update-set-state */
			this.setState( { activeFilters } );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	getInstanceNumber( key ) {
		if ( ! this.instanceCounts.hasOwnProperty( key ) ) {
			this.instanceCounts[ key ] = 1;
		}

		return this.instanceCounts[ key ]++;
	}

	onMatchChange( match ) {
		const { onAdvancedFilterAction } = this.props;

		this.setState( { match } );

		onAdvancedFilterAction( 'match', { match } );
	}

	onFilterChange( index, { property, value, shouldResetValue = false } ) {
		const newActiveFilters = [ ...this.state.activeFilters ];
		newActiveFilters[ index ] = {
			...newActiveFilters[ index ],
			[ property ]: value,
			...( shouldResetValue === true ? { value: null } : {} ),
		};

		this.setState( { activeFilters: newActiveFilters } );
	}

	removeFilter( index ) {
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
		const allFilterKeys = Object.keys( config.filters );

		// Get filter objects with keys.
		const allFilters = allFilterKeys.map( ( key ) => ( {
			key,
			...config.filters[ key ],
		} ) );

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

	addFilter( key, onClose ) {
		const { onAdvancedFilterAction, config } = this.props;
		const filterConfig = config.filters[ key ];
		const newFilter = { key };
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
			const addedFilter = this.filterListRef.current.querySelector(
				'li:last-of-type fieldset'
			);
			addedFilter.focus();
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

	getUpdateHref( activeFilters, matchValue ) {
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

	orderFilters( a, b ) {
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
					<CardBody size={ null }>
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

AdvancedFilters.propTypes = {
	/**
	 * The configuration object required to render filters.
	 */
	config: PropTypes.shape( {
		title: PropTypes.string,
		filters: PropTypes.objectOf(
			PropTypes.shape( {
				labels: PropTypes.shape( {
					add: PropTypes.string,
					remove: PropTypes.string,
					rule: PropTypes.string,
					title: PropTypes.string,
					filter: PropTypes.string,
				} ),
				rules: PropTypes.arrayOf( PropTypes.object ),
				input: PropTypes.object,
			} )
		),
	} ).isRequired,
	/**
	 * Name of this filter, used in translations.
	 */
	path: PropTypes.string.isRequired,
	/**
	 * The query string represented in object form.
	 */
	query: PropTypes.object,
	/**
	 * Function to be called after an advanced filter action has been taken.
	 */
	onAdvancedFilterAction: PropTypes.func,
	/**
	 * The locale for the site.
	 */
	siteLocale: PropTypes.string,
	/**
	 * The currency formatting instance for the site.
	 */
	currency: PropTypes.object.isRequired,
};

AdvancedFilters.defaultProps = {
	query: {},
	onAdvancedFilterAction: () => {},
	siteLocale: 'en_US',
};

export default AdvancedFilters;
