/** @format */
/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { partial } from 'lodash';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Search from 'components/search';

class SearchFilter extends Component {
	constructor() {
		super();
		this.onSearchChange = this.onSearchChange.bind( this );
	}

	onSearchChange( values ) {
		const { filter, onFilterChange } = this.props;
		const nextValues = values.map( value => value.id );
		onFilterChange( filter.key, 'value', nextValues );
	}

	render() {
		const { filter, config, onFilterChange } = this.props;
		const { key, rule, value } = filter;
		const selected = value.map( id => {
			// For now
			return {
				id: parseInt( id, 10 ),
				label: id.toString(),
			};
		} );
		return (
			<Fragment>
				<div className="woocommerce-filters-advanced__fieldset-legend">{ config.label }</div>
				{ rule && (
					<SelectControl
						className="woocommerce-filters-advanced__list-specifier"
						options={ config.rules }
						value={ rule }
						onChange={ partial( onFilterChange, key, 'rule' ) }
						aria-label={ sprintf( __( 'Select a %s filter match', 'wc-admin' ), config.addLabel ) }
					/>
				) }
				<div className="woocommerce-filters-advanced__list-selector">
					<Search
						onChange={ this.onSearchChange }
						type={ config.input.type }
						selected={ selected }
					/>
				</div>
			</Fragment>
		);
	}
}

SearchFilter.propTypes = {
	/**
	 * The configuration object for the single filter to be rendered.
	 */
	config: PropTypes.shape( {
		label: PropTypes.string,
		addLabel: PropTypes.string,
		rules: PropTypes.arrayOf( PropTypes.object ),
		input: PropTypes.object,
	} ).isRequired,
	/**
	 * The activeFilter handed down by AdvancedFilters.
	 */
	filter: PropTypes.shape( {
		key: PropTypes.string,
		rule: PropTypes.string,
		value: PropTypes.array,
	} ).isRequired,
	/**
	 * Function to be called on update.
	 */
	onFilterChange: PropTypes.func.isRequired,
};

export default SearchFilter;
