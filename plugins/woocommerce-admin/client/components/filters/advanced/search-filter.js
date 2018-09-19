/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { partial } from 'lodash';
import PropTypes from 'prop-types';
import { withInstanceId } from '@wordpress/compose';

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
		const { config, filter, instanceId, onFilterChange } = this.props;
		const { selected } = this.state;
		const { key, rule } = filter;
		const { input, labels, rules } = config;
		return (
			<Fragment>
				<div
					id={ `${ key }-${ instanceId }` }
					className="woocommerce-filters-advanced__fieldset-legend"
				>
					{ labels.title }
				</div>
				{ rule && (
					<SelectControl
						className="woocommerce-filters-advanced__list-specifier"
						options={ rules }
						value={ rule }
						onChange={ partial( onFilterChange, key, 'rule' ) }
						aria-label={ labels.rule }
					/>
				) }
				<div className="woocommerce-filters-advanced__list-selector">
					<Search
						onChange={ this.onSearchChange }
						type={ input.type }
						placeholder={ labels.placeholder }
						selected={ selected }
						ariaLabelledby={ `${ key }-${ instanceId }` }
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
		labels: PropTypes.shape( {
			placeholder: PropTypes.string,
			rule: PropTypes.string,
			title: PropTypes.string,
		} ),
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

export default withInstanceId( SearchFilter );
