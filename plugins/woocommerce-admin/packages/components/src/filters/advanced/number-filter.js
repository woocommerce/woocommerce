/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { SelectControl, TextControl } from '@wordpress/components';
import { get, find, partial } from 'lodash';
import interpolateComponents from 'interpolate-components';
import classnames from 'classnames';
import { _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TextControlWithAffixes from '../../text-control-with-affixes';

class NumberFilter extends Component {
	getBetweenString() {
		return _x(
			'{{moreThan /}} and {{lessThan /}}',
			'Numerical range inputs arranged on a single line',
			'wc-admin'
		);
	}

	getLegend( filter, config ) {
		const rule = find( config.rules, { value: filter.rule } ) || {};
		let filterStr = filter.value || '';

		if ( 'between' === rule.value ) {
			const [ moreThan, lessThan ] = filterStr.split( ',' );
			filterStr = interpolateComponents( {
				mixedString: this.getBetweenString(),
				components: {
					moreThan: <Fragment>{ moreThan }</Fragment>,
					lessThan: <Fragment>{ lessThan }</Fragment>,
				},
			} );
		}

		return interpolateComponents( {
			mixedString: config.labels.title,
			components: {
				filter: <span>{ filterStr }</span>,
				rule: <span>{ rule.label }</span>,
			},
		} );
	}

	getFormControl( type, value, onChange ) {
		if ( 'currency' === type ) {
			const currencySymbol = get( wcSettings, [ 'currency', 'symbol' ] );
			const symbolPosition = get( wcSettings, [ 'currency', 'position' ] );

			return (
				0 === symbolPosition.indexOf( 'right' )
				? <TextControlWithAffixes
					suffix={ <span dangerouslySetInnerHTML={ { __html: currencySymbol } } /> }
					className="woocommerce-filters-advanced__input-numeric-range"
					type="number"
					value={ value }
					onChange={ onChange }
				/>
				: <TextControlWithAffixes
					prefix={ <span dangerouslySetInnerHTML={ { __html: currencySymbol } } /> }
					className="woocommerce-filters-advanced__input-numeric-range"
					type="number"
					value={ value }
					onChange={ onChange }
				/>
			);
		}

		return (
			<TextControl
				className="woocommerce-filters-advanced__input-numeric-range"
				type="number"
				value={ value }
				onChange={ onChange }
			/>
		);
	}

	getFilterInputs() {
		const { config, filter, onFilterChange } = this.props;
		const { rule, value } = filter;
		const inputType = get( config, [ 'input', 'type' ], 'number' );

		if ( 'between' === rule ) {
			return this.getRangeInput();
		}

		let inputValue = value;

		if ( value && value.indexOf( ',' ) > -1 ) {
			const [ moreThan, lessThan ] = value.split( ',' );
			inputValue = 'lessthan' === rule ? lessThan : moreThan;
			onFilterChange( filter.key, 'value', inputValue );
		}

		return this.getFormControl(
			inputType,
			inputValue,
			partial( onFilterChange, filter.key, 'value' )
		);
	}

	getRangeInput() {
		const { config, filter, onFilterChange } = this.props;
		const inputType = get( config, [ 'input', 'type' ], 'number' );
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
			mixedString: this.getBetweenString(),
			components: {
				lessThan: this.getFormControl( inputType, lessThan, lessThanOnChange ),
				moreThan: this.getFormControl( inputType, moreThan, moreThanOnChange ),
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
					<div>{ this.getFilterInputs() }</div>
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
