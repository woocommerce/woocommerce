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
	constructor( { config } ) {
		super( ...arguments );

		this.rules = config.rules || [
			{
				value: 'lessthan',
				/* translators: Sentence fragment, logical "Less Than" (x < 10). Screenshot for context: https://cloudup.com/cZaDYBpzqsN */
				label: _x( 'Less Than', 'number', 'wc-admin' ),
			},
			{
				value: 'morethan',
				/* translators: Sentence fragment, logical "More Than" (x > 10). Screenshot for context: https://cloudup.com/cZaDYBpzqsN */
				label: _x( 'More Than', 'number', 'wc-admin' ),
			},
			{
				value: 'between',
				/* eslint-disable-next-line max-len */
				/* translators: Sentence fragment, logical "Between" (1 < x < 10). Screenshot for context: https://cloudup.com/cZaDYBpzqsN */
				label: _x( 'Between', 'number', 'wc-admin' ),
			},
		];
	}

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
			mixedString: '{{moreThan /}} and {{lessThan /}}',
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
		const { labels } = config;

		const children = interpolateComponents( {
			mixedString: labels.title,
			components: {
				filter: (
					<Fragment>
						<SelectControl
							className="woocommerce-filters-advanced__rule"
							options={ this.rules }
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
