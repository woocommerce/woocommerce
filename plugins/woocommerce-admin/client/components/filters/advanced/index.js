/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, Fragment, createRef } from '@wordpress/element';
import { SelectControl, Button, FormTokenField, Dropdown, IconButton } from '@wordpress/components';
import { partial, findIndex, find, difference } from 'lodash';
import PropTypes from 'prop-types';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import Card from 'components/card';
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
		this.state = {
			match: matches[ 0 ],
			activeFilters: [
				/**
				 * Example activeFilter
				 * { key: ‘product’, rule: ‘includes’, value: [ ‘one’, ‘two’ ] }
				 */
			],
		};

		this.filterListRef = createRef();

		this.onMatchChange = this.onMatchChange.bind( this );
		this.onFilterChange = this.onFilterChange.bind( this );
		this.getSelector = this.getSelector.bind( this );
		this.getAvailableFilterKeys = this.getAvailableFilterKeys.bind( this );
		this.addFilter = this.addFilter.bind( this );
		this.removeFilter = this.removeFilter.bind( this );
		this.clearAllFilters = this.clearAllFilters.bind( this );
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

	getSelector( filter ) {
		const filterConfig = this.props.config[ filter.key ];
		const { input } = filterConfig;
		if ( ! input ) {
			return null;
		}
		if ( 'SelectControl' === input.component ) {
			return (
				<SelectControl
					className="woocommerce-filters-advanced__list-select"
					options={ input.options }
					value={ filter.value }
					onChange={ partial( this.onFilterChange, filter.key, 'value' ) }
					aria-label={ sprintf( __( 'Select %s', 'wc-admin' ), filterConfig.label ) }
				/>
			);
		}
		if ( 'FormTokenField' === input.component ) {
			return (
				<FormTokenField
					value={ filter.value }
					onChange={ partial( this.onFilterChange, filter.key, 'value' ) }
					placeholder={ sprintf( __( 'Add %s', 'wc-admin' ), filterConfig.label ) }
				/>
			);
		}
		return null;
	}

	getAvailableFilterKeys() {
		const { config } = this.props;
		const activeFilterKeys = this.state.activeFilters.map( f => f.key );
		return difference( Object.keys( config ), activeFilterKeys );
	}

	addFilter( key, onClose ) {
		const filterConfig = this.props.config[ key ];
		const newFilter = { key, rule: filterConfig.rules[ 0 ] };
		if ( filterConfig.input && filterConfig.input.options ) {
			newFilter.value = filterConfig.input.options[ 0 ];
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

	clearAllFilters() {
		this.setState( {
			activeFilters: [],
		} );
	}

	render() {
		const { config } = this.props;
		const availableFilterKeys = this.getAvailableFilterKeys();
		return (
			<Card className="woocommerce-filters-advanced" title={ this.getTitle() }>
				<ul className="woocommerce-filters-advanced__list" ref={ this.filterListRef }>
					{ this.state.activeFilters.map( filter => {
						const { key, rule } = filter;
						const filterConfig = config[ key ];
						return (
							<li className="woocommerce-filters-advanced__list-item" key={ key }>
								{ /*eslint-disable-next-line jsx-a11y/no-noninteractive-tabindex*/ }
								<fieldset tabIndex="0">
									{ /*eslint-enable-next-line jsx-a11y/no-noninteractive-tabindex*/ }
									<legend className="screen-reader-text">{ filterConfig.label }</legend>
									<div className="woocommerce-filters-advanced__fieldset">
										<div className="woocommerce-filters-advanced__fieldset-legend">
											{ filterConfig.label }
										</div>
										<SelectControl
											className="woocommerce-filters-advanced__list-specifier"
											options={ filterConfig.rules }
											value={ rule }
											onChange={ partial( this.onFilterChange, key, 'rule' ) }
											aria-label={ sprintf(
												__( 'Select a %s filter match', 'wc-admin' ),
												filterConfig.addLabel
											) }
										/>
										<div className="woocommerce-filters-advanced__list-selector">
											{ this.getSelector( filter ) }
										</div>
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
					<Button isPrimary>{ __( 'Filter', 'wc-admin' ) }</Button>
					<Button isLink onClick={ this.clearAllFilters }>
						{ __( 'Clear all filters', 'wc-admin' ) }
					</Button>
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
