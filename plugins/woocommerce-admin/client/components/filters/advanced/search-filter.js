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
	constructor( { filter, config } ) {
		super( ...arguments );
		this.onSearchChange = this.onSearchChange.bind( this );
		this.state = {
			selected: [],
		};

		this.updateLabels = this.updateLabels.bind( this );

		if ( filter.value.length ) {
			config.input.getLabels( filter.value ).then( this.updateLabels );
		}
	}

	updateLabels( data ) {
		const selected = data.map( p => ( { id: p.id, label: p.name } ) );
		this.setState( { selected } );
	}

	onSearchChange( values ) {
		this.setState( {
			selected: values,
		} );
		const { filter, onFilterChange } = this.props;
		const idList = values.map( value => value.id ).join( ',' );
		onFilterChange( filter.key, 'value', idList );
	}

	render() {
		const { filter, config, onFilterChange } = this.props;
		const { selected } = this.state;
		const { key, rule } = filter;
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
		value: PropTypes.string,
	} ).isRequired,
	/**
	 * Function to be called on update.
	 */
	onFilterChange: PropTypes.func.isRequired,
};

export default SearchFilter;
