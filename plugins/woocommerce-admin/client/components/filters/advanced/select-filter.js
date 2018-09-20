/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { SelectControl, Spinner } from '@wordpress/components';
import { find, partial } from 'lodash';
import PropTypes from 'prop-types';
import interpolateComponents from 'interpolate-components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { getDefaultOptionValue } from './utils';

class SelectFilter extends Component {
	constructor( { filter, config, onFilterChange } ) {
		super( ...arguments );

		const options = config.input.options;
		this.state = { options };

		this.updateOptions = this.updateOptions.bind( this );

		if ( ! options && config.input.getOptions ) {
			config.input
				.getOptions()
				.then( this.updateOptions )
				.then( returnedOptions => {
					if ( ! filter.value ) {
						const value = getDefaultOptionValue( config, returnedOptions );
						onFilterChange( filter.key, 'value', value );
					}
				} );
		}
	}

	updateOptions( options ) {
		this.setState( { options } );
		return options;
	}

	getLegend( filter, config ) {
		const rule = find( config.rules, { value: filter.rule } ) || {};
		const value = find( config.input.options, { value: filter.value } ) || {};
		return interpolateComponents( {
			mixedString: config.labels.title,
			components: {
				filter: <span>{ value.label }</span>,
				rule: <span>{ rule.label }</span>,
			},
		} );
	}

	render() {
		const { config, filter, onFilterChange, isEnglish } = this.props;
		const { options } = this.state;
		const { key, rule, value } = filter;
		const { labels, rules } = config;
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
				filter: options ? (
					<SelectControl
						className="woocommerce-filters-advanced__input"
						options={ options }
						value={ value }
						onChange={ partial( onFilterChange, filter.key, 'value' ) }
						aria-label={ labels.filter }
					/>
				) : (
					<Spinner />
				),
			},
		} );
		/*eslint-disable jsx-a11y/no-noninteractive-tabindex*/
		return (
			<fieldset tabIndex="0">
				<legend className="screen-reader-text">{ this.getLegend( filter, config ) }</legend>
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

SelectFilter.propTypes = {
	/**
	 * The configuration object for the single filter to be rendered.
	 */
	config: PropTypes.shape( {
		labels: PropTypes.shape( {
			rule: PropTypes.string,
			title: PropTypes.string,
			filter: PropTypes.string,
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

export default SelectFilter;
