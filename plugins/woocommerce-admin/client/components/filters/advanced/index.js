/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, Fragment, createRef } from '@wordpress/element';
import { SelectControl, Button, Dropdown, IconButton } from '@wordpress/components';
import { partial, findIndex, find, difference } from 'lodash';
import PropTypes from 'prop-types';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import Card from 'components/card';
import Link from 'components/link';
import SelectFilter from './select-filter';
import SearchFilter from './search-filter';
import { getActiveFiltersFromQuery, getQueryFromActiveFilters } from './utils';
import { getNewPath } from 'lib/nav-utils';
import './style.scss';

const matches = [
	{ value: 'all', label: __( 'All', 'wc-admin' ) },
	{ value: 'any', label: __( 'Any', 'wc-admin' ) },
];

/**
 * Displays a configurable set of filters which can modify query parameters.
 */
class AdvancedFilters extends Component {
	constructor( props ) {
		super( props );
		const activeFiltersFromQuery = getActiveFiltersFromQuery( props.query, props.config );
		this.state = {
			match: matches[ 0 ],
			activeFilters: activeFiltersFromQuery,
			previousFilters: activeFiltersFromQuery,
		};

		this.filterListRef = createRef();

		this.onMatchChange = this.onMatchChange.bind( this );
		this.onFilterChange = this.onFilterChange.bind( this );
		this.getAvailableFilterKeys = this.getAvailableFilterKeys.bind( this );
		this.addFilter = this.addFilter.bind( this );
		this.removeFilter = this.removeFilter.bind( this );
		this.clearFilters = this.clearFilters.bind( this );
		this.getUpdateHref = this.getUpdateHref.bind( this );
	}

	onMatchChange( value ) {
		this.setState( {
			match: find( matches, match => value === match.value ),
		} );
	}

	onFilterChange( key, property, value ) {
		const activeFilters = this.state.activeFilters.map( activeFilter => {
			if ( key === activeFilter.key ) {
				return Object.assign( {}, activeFilter, { [ property ]: value } );
			}
			return activeFilter;
		} );

		this.setState( { activeFilters } );
	}

	removeFilter( key ) {
		const activeFilters = [ ...this.state.activeFilters ];
		const index = findIndex( activeFilters, filter => filter.key === key );
		activeFilters.splice( index, 1 );
		this.setState( { activeFilters } );
	}

	getTitle() {
		const { match } = this.state;
		const { filterTitle } = this.props;
		return (
			<Fragment>
				<span>{ sprintf( __( '%s Match', 'wc-admin' ), filterTitle ) }</span>
				<SelectControl
					className="woocommerce-filters-advanced__title-select"
					options={ matches }
					value={ match.value }
					onChange={ this.onMatchChange }
					aria-label={ __( 'Match any or all filters', 'wc-admin' ) }
				/>
				<span>{ __( 'Filters', 'wc-admin' ) }</span>
			</Fragment>
		);
	}

	getAvailableFilterKeys() {
		const { config } = this.props;
		const activeFilterKeys = this.state.activeFilters.map( f => f.key );
		return difference( Object.keys( config ), activeFilterKeys );
	}

	addFilter( key, onClose ) {
		const filterConfig = this.props.config[ key ];
		const newFilter = { key };
		if ( Array.isArray( filterConfig.rules ) && filterConfig.rules.length ) {
			newFilter.rule = filterConfig.rules[ 0 ].value;
		}
		if ( filterConfig.input && filterConfig.input.options ) {
			newFilter.value = filterConfig.input.options[ 0 ].value;
		}
		if ( filterConfig.input && 'Search' === filterConfig.input.component ) {
			newFilter.value = [];
		}
		this.setState( state => {
			return {
				activeFilters: [ ...state.activeFilters, newFilter ],
			};
		} );
		onClose();
		// after render, focus the newly added filter's first focusable element
		setTimeout( () => {
			const addedFilter = this.filterListRef.current.querySelector( 'li:last-of-type fieldset' );
			addedFilter.focus();
		} );
	}

	clearFilters() {
		this.setState( {
			activeFilters: [],
		} );
	}

	getUpdateHref( activeFilters ) {
		const { previousFilters } = this.state;
		const { path, query } = this.props;
		const updatedQuery = getQueryFromActiveFilters( activeFilters, previousFilters );
		return getNewPath( updatedQuery, path, query );
	}

	render() {
		const { config } = this.props;
		const { activeFilters } = this.state;
		const availableFilterKeys = this.getAvailableFilterKeys();
		const updateHref = this.getUpdateHref( activeFilters );
		const updateDisabled = window.location.hash && window.location.hash.substr( 1 ) === updateHref;
		return (
			<Card className="woocommerce-filters-advanced" title={ this.getTitle() }>
				<ul className="woocommerce-filters-advanced__list" ref={ this.filterListRef }>
					{ activeFilters.map( filter => {
						const { key } = filter;
						const filterConfig = config[ key ];
						return (
							<li className="woocommerce-filters-advanced__list-item" key={ key }>
								{ /*eslint-disable-next-line jsx-a11y/no-noninteractive-tabindex*/ }
								<fieldset tabIndex="0">
									{ /*eslint-enable-next-line jsx-a11y/no-noninteractive-tabindex*/ }
									<legend className="screen-reader-text">{ filterConfig.label }</legend>
									<div className="woocommerce-filters-advanced__fieldset">
										{ 'SelectControl' === filterConfig.input.component && (
											<SelectFilter
												filter={ filter }
												config={ filterConfig }
												onFilterChange={ this.onFilterChange }
											/>
										) }
										{ 'Search' === filterConfig.input.component && (
											<SearchFilter
												filter={ filter }
												config={ filterConfig }
												onFilterChange={ this.onFilterChange }
											/>
										) }
									</div>
								</fieldset>
								<IconButton
									className="woocommerce-filters-advanced__remove"
									label={ sprintf( __( 'Remove %s filter', 'wc-admin' ), filterConfig.label ) }
									onClick={ partial( this.removeFilter, key ) }
									icon={ <Gridicon icon="cross-small" /> }
								/>
							</li>
						);
					} ) }
				</ul>
				{ availableFilterKeys.length > 0 && (
					<div className="woocommerce-filters-advanced__add-filter">
						<Dropdown
							position="bottom center"
							renderToggle={ ( { isOpen, onToggle } ) => (
								<IconButton
									className="woocommerce-filters-advanced__add-button"
									icon={ <Gridicon icon="add-outline" /> }
									onClick={ onToggle }
									aria-expanded={ isOpen }
								>
									{ __( 'Add a Filter', 'wc-admin' ) }
								</IconButton>
							) }
							renderContent={ ( { onClose } ) => (
								<ul className="woocommerce-filters-advanced__add-dropdown">
									{ availableFilterKeys.map( key => (
										<li key={ key }>
											<Button onClick={ partial( this.addFilter, key, onClose ) }>
												{ config[ key ].addLabel }
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
							{ __( 'Filter', 'wc-admin' ) }
						</Button>
					) }
					{ ! updateDisabled && (
						<Link
							className="components-button is-primary is-button"
							type="wc-admin"
							href={ updateHref }
						>
							{ __( 'Filter', 'wc-admin' ) }
						</Link>
					) }
					<Link type="wc-admin" href={ this.getUpdateHref( [] ) } onClick={ this.clearFilters }>
						{ __( 'Clear all filters', 'wc-admin' ) }
					</Link>
				</div>
			</Card>
		);
	}
}

AdvancedFilters.propTypes = {
	/**
	 * The configuration object required to render filters.
	 */
	config: PropTypes.objectOf(
		PropTypes.shape( {
			label: PropTypes.string,
			addLabel: PropTypes.string,
			rules: PropTypes.arrayOf( PropTypes.object ),
			input: PropTypes.object,
		} )
	).isRequired,
	/**
	 * Name of this filter, used in translations.
	 */
	filterTitle: PropTypes.string.isRequired,
	/**
	 * The `path` parameter supplied by React-Router.
	 */
	path: PropTypes.string.isRequired,
	/**
	 * The query string represented in object form.
	 */
	query: PropTypes.object,
};

AdvancedFilters.defaultProps = {
	query: {},
};

export default AdvancedFilters;
