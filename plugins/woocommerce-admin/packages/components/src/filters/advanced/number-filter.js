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
import { formatCurrency } from '@woocommerce/currency';

class NumberFilter extends Component {
	getBetweenString() {
		return _x(
			'{{rangeStart /}}{{span}}and{{/span}}{{rangeEnd /}}',
			'Numerical range inputs arranged on a single line',
			'wc-admin'
		);
	}

	getLegend( filter, config ) {
		const inputType = get( config, [ 'input', 'type' ], 'number' );
		const rule = find( config.rules, { value: filter.rule } ) || {};
		let [ rangeStart, rangeEnd ] = ( filter.value || '' ).split( ',' );

		if ( 'currency' === inputType ) {
			rangeStart = formatCurrency( rangeStart );
			rangeEnd = formatCurrency( rangeEnd );
		}

		let filterStr = rangeStart;

		if ( 'between' === rule.value ) {
			filterStr = interpolateComponents( {
				mixedString: this.getBetweenString(),
				components: {
					rangeStart: <Fragment>{ rangeStart }</Fragment>,
					rangeEnd: <Fragment>{ rangeEnd }</Fragment>,
					span: <Fragment />,
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
					className="woocommerce-filters-advanced__input"
					type="number"
					value={ value || '' }
					onChange={ onChange }
				/>
				: <TextControlWithAffixes
					prefix={ <span dangerouslySetInnerHTML={ { __html: currencySymbol } } /> }
					className="woocommerce-filters-advanced__input"
					type="number"
					value={ value || '' }
					onChange={ onChange }
				/>
			);
		}

		return (
			<TextControl
				className="woocommerce-filters-advanced__input"
				type="number"
				value={ value || '' }
				onChange={ onChange }
			/>
		);
	}

	getFilterInputs() {
		const { config, filter, onFilterChange } = this.props;
		const inputType = get( config, [ 'input', 'type' ], 'number' );

		if ( 'between' === filter.rule ) {
			return this.getRangeInput();
		}

		const [ rangeStart, rangeEnd ] = ( filter.value || '' ).split( ',' );
		if ( Boolean( rangeEnd ) ) {
			// If there's a value for rangeEnd, we've just changed from "between"
			// to "less than" or "more than" and need to transition the value
			onFilterChange( filter.key, 'value', rangeStart || rangeEnd );
		}

		return this.getFormControl(
			inputType,
			rangeStart || rangeEnd,
			partial( onFilterChange, filter.key, 'value' )
		);
	}

	getRangeInput() {
		const { config, filter, onFilterChange } = this.props;
		const inputType = get( config, [ 'input', 'type' ], 'number' );
		const [ rangeStart, rangeEnd ] = ( filter.value || '' ).split( ',' );

		const rangeStartOnChange = ( newRangeStart ) => {
			const newValue = [ newRangeStart, rangeEnd ].join( ',' );
			onFilterChange( filter.key, 'value', newValue );
		};

		const rangeEndOnChange = ( newRangeEnd ) => {
			const newValue = [ rangeStart, newRangeEnd ].join( ',' );
			onFilterChange( filter.key, 'value', newValue );
		};

		return interpolateComponents( {
			mixedString: this.getBetweenString(),
			components: {
				rangeStart: this.getFormControl( inputType, rangeStart, rangeStartOnChange ),
				rangeEnd: this.getFormControl( inputType, rangeEnd, rangeEndOnChange ),
				span: <span className="separator" />,
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
					<div
						className={ classnames( 'woocommerce-filters-advanced__input-numeric-range', {
							'is-between': 'between' === rule,
						} ) }
					>
						{ this.getFilterInputs() }
					</div>
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
