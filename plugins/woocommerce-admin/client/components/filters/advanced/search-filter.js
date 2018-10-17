/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { find, partial } from 'lodash';
import PropTypes from 'prop-types';
import interpolateComponents from 'interpolate-components';
import classnames from 'classnames';

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

	updateLabels( selected ) {
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

	getLegend( filter, config ) {
		const { selected } = this.state;
		const rule = find( config.rules, { value: filter.rule } ) || {};
		const filterStr = selected.map( item => item.label ).join( ', ' );

		return interpolateComponents( {
			mixedString: config.labels.title,
			components: {
				filter: <span>{ filterStr }</span>,
				rule: <span>{ rule.label }</span>,
			},
		} );
	}

	render() {
		const { config, filter, onFilterChange, isEnglish } = this.props;
		const { selected } = this.state;
		const { key, rule } = filter;
		const { input, labels, rules } = config;
		const children = interpolateComponents( {
			mixedString: labels.title,
			components: {
				rule: (
					<SelectControl
						className="woocommerce-filters-advanced__rule"
						options={ rules }
						value={ rule }
						onChange={ partial( onFilterChange, key, 'rule' ) }
						aria-label={ labels.rule }
					/>
				),
				filter: (
					<Search
						className="woocommerce-filters-advanced__input"
						onChange={ this.onSearchChange }
						type={ input.type }
						placeholder={ labels.placeholder }
						selected={ selected }
						inlineTags
						aria-label={ labels.filter }
					/>
				),
			},
		} );
		/*eslint-disable jsx-a11y/no-noninteractive-tabindex*/
		return (
			<fieldset tabIndex="0">
				<legend className="screen-reader-text">
					{ this.getLegend( filter, config, selected ) }
				</legend>
				<div
					className={ classnames( 'woocommerce-filters-advanced__fieldset', {
						'is-english': isEnglish,
					} ) }
				>
					{ children }
				</div>
			</fieldset>
		);
		/*eslint-enable jsx-a11y/no-noninteractive-tabindex*/
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

export default SearchFilter;
