/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { SelectControl, TextControl } from '@wordpress/components';
import { find, partial } from 'lodash';
import interpolateComponents from 'interpolate-components';
import classnames from 'classnames';
import { _x } from '@wordpress/i18n';

class NumberFilter extends Component {
	getLegend( filter, config ) {
		const rule = find( config.rules, { value: filter.rule } ) || {};
		const filterStr = '';

		return interpolateComponents( {
			mixedString: config.labels.title,
			components: {
				filter: <span>{ filterStr }</span>,
				rule: <span>{ rule.label }</span>,
			},
		} );
	}

	getFilterInput() {
		const { filter, onFilterChange } = this.props;
		const { rule, value } = filter;

		if ( 'between' === rule ) {
			return this.getRangeInput();
		}

		return (
			<TextControl
				type="number"
				value={ value }
				onChange={ partial( onFilterChange, filter.key, 'value' ) }
			/>
		);
	}

	getRangeInput() {
		const { filter, onFilterChange } = this.props;
		const { value } = filter;
		const [ moreThan, lessThan ] = ( value || '' ).split( ',' );

		const moreThanOnChange = ( newMoreThan ) => {
			const newValue = [ newMoreThan, lessThan ].join( ',' );
			onFilterChange( filter.key, 'value', newValue );
		};

		const lessThanOnChange = ( newLessThan ) => {
			const newValue = [ moreThan, newLessThan ].join( ',' );
			onFilterChange( filter.key, 'value', newValue );
		};

		return interpolateComponents( {
			mixedString: _x(
				'{{moreThan /}} and {{lessThan /}}',
				'Numerical range inputs arranged on a single line',
				'wc-admin'
			),
			components: {
				lessThan: (
					<TextControl
						type="number"
						value={ lessThan }
						onChange={ lessThanOnChange }
					/>
				),
				moreThan: (
					<TextControl
						type="number"
						value={ moreThan }
						onChange={ moreThanOnChange }
					/>
				),
			},
		} );
	}

	render() {
		const { config, filter, onFilterChange, isEnglish } = this.props;
		const { key, rule } = filter;
		const { labels, rules } = config;

		const children = interpolateComponents( {
			mixedString: labels.title,
			components: {
				filter: (
					<Fragment>
						<SelectControl
							className="woocommerce-filters-advanced__rule"
							options={ rules }
							value={ rule }
							onChange={ partial( onFilterChange, key, 'rule' ) }
							aria-label={ labels.rule }
						/>
						<div>{ this.getFilterInput() }</div>
					</Fragment>
				),
			},
		} );
		/*eslint-disable jsx-a11y/no-noninteractive-tabindex*/
		return (
			<fieldset tabIndex="0">
				<legend className="screen-reader-text">
					{ this.getLegend( filter, config ) }
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

export default NumberFilter;
