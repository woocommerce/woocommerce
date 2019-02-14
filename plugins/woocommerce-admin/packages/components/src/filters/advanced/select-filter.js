/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { SelectControl, Spinner } from '@wordpress/components';
import { find, partial } from 'lodash';
import PropTypes from 'prop-types';
import interpolateComponents from 'interpolate-components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { textContent } from './utils';

/**
 * WooCommerce dependencies
 */
import { getDefaultOptionValue } from '@woocommerce/navigation';

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

	getScreenReaderText( filter, config ) {
		if ( '' === filter.value ) {
			return '';
		}

		const rule = find( config.rules, { value: filter.rule } ) || {};
		const value = find( config.input.options, { value: filter.value } ) || {};

		return textContent( interpolateComponents( {
			mixedString: config.labels.title,
			components: {
				filter: <Fragment>{ value.label }</Fragment>,
				rule: <Fragment>{ rule.label }</Fragment>,
			},
		} ) );
	}

	render() {
		const { className, config, filter, onFilterChange, isEnglish } = this.props;
		const { options } = this.state;
		const { key, rule, value } = filter;
		const { labels, rules } = config;
		const children = interpolateComponents( {
			mixedString: labels.title,
			components: {
				title: <span className={ className } />,
				rule: (
					<SelectControl
						className={ classnames( className, 'woocommerce-filters-advanced__rule' ) }
						options={ rules }
						value={ rule }
						onChange={ partial( onFilterChange, key, 'rule' ) }
						aria-label={ labels.rule }
					/>
				),
				filter: options ? (
					<SelectControl
						className={ classnames( className, 'woocommerce-filters-advanced__input' ) }
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

		const screenReaderText = this.getScreenReaderText( filter, config );

		/*eslint-disable jsx-a11y/no-noninteractive-tabindex*/
		return (
			<fieldset className="woocommerce-filters-advanced__line-item" tabIndex="0">
				<legend className="screen-reader-text">
					{ labels.add || '' }
				</legend>
				<div
					className={ classnames( 'woocommerce-filters-advanced__fieldset', {
						'is-english': isEnglish,
					} ) }
				>
					{ children }
				</div>
				{ screenReaderText && (
					<span className="screen-reader-text">
						{ screenReaderText }
					</span>
				) }
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
