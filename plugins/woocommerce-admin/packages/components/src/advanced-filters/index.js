/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, createRef } from '@wordpress/element';
import { SelectControl, Button, Dropdown } from '@wordpress/components';
import { partial, findIndex, difference, isEqual } from 'lodash';
import PropTypes from 'prop-types';
import Gridicon from 'gridicons';
import interpolateComponents from 'interpolate-components';
import classnames from 'classnames';

/**
 * WooCommerce dependencies
 */
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
import Card from '../card';
import Link from '../link';
import SelectFilter from './select-filter';
import SearchFilter from './search-filter';
import NumberFilter from './number-filter';
import DateFilter from './date-filter';

const matches = [
	{ value: 'all', label: __( 'All', 'woocommerce-admin' ) },
	{ value: 'any', label: __( 'Any', 'woocommerce-admin' ) },
];

/**
 * Displays a configurable set of filters which can modify query parameters.
 */
class AdvancedFilters extends Component {
	constructor( { query, config } ) {
		super( ...arguments );
		this.state = {
			match: query.match || 'all',
			activeFilters: getActiveFiltersFromQuery( query, config.filters ),
		};

		this.filterListRef = createRef();

		this.onMatchChange = this.onMatchChange.bind( this );
		this.onFilterChange = this.onFilterChange.bind( this );
		this.getAvailableFilterKeys = this.getAvailableFilterKeys.bind( this );
		this.addFilter = this.addFilter.bind( this );
		this.removeFilter = this.removeFilter.bind( this );
		this.clearFilters = this.clearFilters.bind( this );
		this.getUpdateHref = this.getUpdateHref.bind( this );
		this.updateFilter = this.updateFilter.bind( this );
		this.onFilter = this.onFilter.bind( this );
	}

	componentDidUpdate( prevProps ) {
		const { config, query } = this.props;
		const { query: prevQuery } = prevProps;

		if ( ! isEqual( prevQuery, query ) ) {
			/* eslint-disable react/no-did-update-set-state */
			this.setState( {
				activeFilters: getActiveFiltersFromQuery(
					query,
					config.filters
				),
			} );
			/* eslint-enable react/no-did-update-set-state */
		}
	}

	onMatchChange( match ) {
		const { onAdvancedFilterAction } = this.props;

		this.setState( { match } );

		onAdvancedFilterAction( 'match', { match } );
	}

	onFilterChange( key, property, value ) {
		const activeFilters = this.state.activeFilters.map(
			( activeFilter ) => {
				if ( key === activeFilter.key ) {
					return Object.assign( {}, activeFilter, {
						[ property ]: value,
					} );
				}
				return activeFilter;
			}
		);

		this.setState( { activeFilters } );
	}

	updateFilter( filter ) {
		const activeFilters = this.state.activeFilters.map(
			( activeFilter ) => {
				if ( filter.key === activeFilter.key ) {
					return filter;
				}
				return activeFilter;
			}
		);

		this.setState( { activeFilters } );
	}

	removeFilter( key ) {
		const { onAdvancedFilterAction } = this.props;
		const activeFilters = [ ...this.state.activeFilters ];
		const index = findIndex(
			activeFilters,
			( filter ) => filter.key === key
		);
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
		return interpolateComponents( {
			mixedString: config.title,
			components: {
				select: (
					<SelectControl
						className="woocommerce-filters-advanced__title-select"
						options={ matches }
						value={ match }
						onChange={ this.onMatchChange }
						aria-label={ __(
							'Choose to apply any or all filters',
							'woocommerce-admin'
						) }
					/>
				),
			},
		} );
	}

	getAvailableFilterKeys() {
		const { config } = this.props;
		const activeFilterKeys = this.state.activeFilters.map( ( f ) => f.key );
		return difference( Object.keys( config.filters ), activeFilterKeys );
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

	render() {
		const { config, query, currency } = this.props;
		const { activeFilters, match } = this.state;
		const availableFilterKeys = this.getAvailableFilterKeys();
		const updateHref = this.getUpdateHref( activeFilters, match );
		const updateDisabled =
			'admin.php' + window.location.search === updateHref ||
			activeFilters.length === 0;
		const isEnglish = this.isEnglish();
		return (
			<Card
				className="woocommerce-filters-advanced woocommerce-analytics__card"
				title={ this.getTitle() }
			>
				<ul
					className="woocommerce-filters-advanced__list"
					ref={ this.filterListRef }
				>
					{ activeFilters.map( ( filter ) => {
						const { key } = filter;
						const { input, labels } = config.filters[ key ];
						return (
							<li
								className="woocommerce-filters-advanced__list-item"
								key={ key }
							>
								{ input.component === 'SelectControl' && (
									<SelectFilter
										className="woocommerce-filters-advanced__fieldset-item"
										filter={ filter }
										config={ config.filters[ key ] }
										onFilterChange={ this.onFilterChange }
										isEnglish={ isEnglish }
									/>
								) }
								{ input.component === 'Search' && (
									<SearchFilter
										className="woocommerce-filters-advanced__fieldset-item"
										filter={ filter }
										config={ config.filters[ key ] }
										onFilterChange={ this.onFilterChange }
										isEnglish={ isEnglish }
										query={ query }
									/>
								) }
								{ input.component === 'Number' && (
									<NumberFilter
										className="woocommerce-filters-advanced__fieldset-item"
										filter={ filter }
										config={ config.filters[ key ] }
										onFilterChange={ this.onFilterChange }
										isEnglish={ isEnglish }
										query={ query }
										currency={ currency }
									/>
								) }
								{ input.component === 'Currency' && (
									<NumberFilter
										className="woocommerce-filters-advanced__fieldset-item"
										filter={ filter }
										config={ {
											...config.filters[ key ],
											...{
												input: {
													type: 'currency',
													component: 'Currency',
												},
											},
										} }
										onFilterChange={ this.onFilterChange }
										isEnglish={ isEnglish }
										query={ query }
										currency={ currency }
									/>
								) }
								{ input.component === 'Date' && (
									<DateFilter
										className="woocommerce-filters-advanced__fieldset-item"
										filter={ filter }
										config={ config.filters[ key ] }
										onFilterChange={ this.onFilterChange }
										isEnglish={ isEnglish }
										query={ query }
										updateFilter={ this.updateFilter }
									/>
								) }
								<Button
									className={ classnames(
										'woocommerce-filters-advanced__line-item',
										'woocommerce-filters-advanced__remove'
									) }
									label={ labels.remove }
									onClick={ partial(
										this.removeFilter,
										key
									) }
								>
									<Gridicon icon="cross-small" />
								</Button>
							</li>
						);
					} ) }
				</ul>
				{ availableFilterKeys.length > 0 && (
					<div className="woocommerce-filters-advanced__add-filter">
						<Dropdown
							className="woocommerce-filters-advanced__add-filter-dropdown"
							position="bottom center"
							renderToggle={ ( { isOpen, onToggle } ) => (
								<Button
									className="woocommerce-filters-advanced__add-button"
									onClick={ onToggle }
									aria-expanded={ isOpen }
								>
									<Gridicon icon="add-outline" />
									{ __(
										'Add a Filter',
										'woocommerce-admin'
									) }
								</Button>
							) }
							renderContent={ ( { onClose } ) => (
								<ul className="woocommerce-filters-advanced__add-dropdown">
									{ availableFilterKeys.map( ( key ) => (
										<li key={ key }>
											<Button
												onClick={ partial(
													this.addFilter,
													key,
													onClose
												) }
											>
												{
													config.filters[ key ].labels
														.add
												}
											</Button>
										</li>
									) ) }
								</ul>
							) }
						/>
					</div>
				) }

				<div className="woocommerce-filters-advanced__controls">
					{ updateDisabled && (
						<Button isPrimary disabled>
							{ __( 'Filter', 'woocommerce-admin' ) }
						</Button>
					) }
					{ ! updateDisabled && (
						<Link
							className="components-button is-primary is-button"
							type="wc-admin"
							href={ updateHref }
							onClick={ this.onFilter }
						>
							{ __( 'Filter', 'woocommerce-admin' ) }
						</Link>
					) }
					{ activeFilters.length > 0 && (
						<Link
							type="wc-admin"
							href={ this.getUpdateHref( [] ) }
							onClick={ this.clearFilters }
						>
							{ __( 'Clear all filters', 'woocommerce-admin' ) }
						</Link>
					) }
				</div>
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
