/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import { SelectControl, TextControl } from '@wordpress/components';
import { get, find, partial, isArray } from 'lodash';
import interpolateComponents from 'interpolate-components';
import classnames from 'classnames';
import { sprintf, __, _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TextControlWithAffixes from '../text-control-with-affixes';
import { textContent } from './utils';

/**
 * WooCommerce dependencies
 */
import { formatCurrency } from '@woocommerce/currency';

class NumberFilter extends Component {
	getBetweenString() {
		return _x(
			'{{rangeStart /}}{{span}} and {{/span}}{{rangeEnd /}}',
			'Numerical range inputs arranged on a single line',
			'woocommerce-admin'
		);
	}

	getScreenReaderText( filter, config ) {
		const inputType = get( config, [ 'input', 'type' ], 'number' );
		const rule = find( config.rules, { value: filter.rule } ) || {};
		let [ rangeStart, rangeEnd ] = isArray( filter.value ) ? filter.value : [ filter.value ];

		// Return nothing if we're missing input(s)
		if (
			! rangeStart ||
			( 'between' === rule.value && ! rangeEnd )
		) {
			return '';
		}

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

		return textContent( interpolateComponents( {
			mixedString: config.labels.title,
			components: {
				filter: <Fragment>{ filterStr }</Fragment>,
				rule: <Fragment>{ rule.label }</Fragment>,
			},
		} ) );
	}

	getFormControl( {
		type,
		value,
		label,
		onChange,
		currencySymbol,
		symbolPosition,
	} ) {
		if ( 'currency' === type ) {
			return (
				0 === symbolPosition.indexOf( 'right' )
				? <TextControlWithAffixes
					suffix={ <span dangerouslySetInnerHTML={ { __html: currencySymbol } } /> }
					className="woocommerce-filters-advanced__input"
					type="number"
					value={ value || '' }
					aria-label={ label }
					onChange={ onChange }
				/>
				: <TextControlWithAffixes
					prefix={ <span dangerouslySetInnerHTML={ { __html: currencySymbol } } /> }
					className="woocommerce-filters-advanced__input"
					type="number"
					value={ value || '' }
					aria-label={ label }
					onChange={ onChange }
				/>
			);
		}

		return (
			<TextControl
				className="woocommerce-filters-advanced__input"
				type="number"
				value={ value || '' }
				aria-label={ label }
				onChange={ onChange }
			/>
		);
	}

	getFilterInputs() {
		const {
			config,
			filter,
			onFilterChange,
			currencySymbol,
			symbolPosition,
		} = this.props;
		const inputType = get( config, [ 'input', 'type' ], 'number' );

		if ( 'between' === filter.rule ) {
			return this.getRangeInput();
		}

		const [ rangeStart, rangeEnd ] = isArray( filter.value ) ? filter.value : [ filter.value ];
		if ( Boolean( rangeEnd ) ) {
			// If there's a value for rangeEnd, we've just changed from "between"
			// to "less than" or "more than" and need to transition the value
			onFilterChange( filter.key, 'value', rangeStart || rangeEnd );
		}

		let labelFormat = '';

		if ( 'lessthan' === filter.rule ) {
			/* eslint-disable-next-line max-len */
			/* translators: Sentence fragment, "maximum amount" refers to a numeric value the field must be less than. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
			labelFormat = _x( '%(field)s maximum amount', 'maximum value input', 'woocommerce-admin' );
		} else {
			/* eslint-disable-next-line max-len */
			/* translators: Sentence fragment, "minimum amount" refers to a numeric value the field must be more than. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
			labelFormat = _x( '%(field)s minimum amount', 'minimum value input', 'woocommerce-admin' );
		}

		return this.getFormControl( {
			type: inputType,
			value: rangeStart || rangeEnd,
			label: sprintf( labelFormat, { field: get( config, [ 'labels', 'add' ] ) } ),
			onChange: partial( onFilterChange, filter.key, 'value' ),
			currencySymbol,
			symbolPosition,
		} );
	}

	getRangeInput() {
		const {
			config,
			filter,
			onFilterChange,
			currencySymbol,
			symbolPosition,
		} = this.props;
		const inputType = get( config, [ 'input', 'type' ], 'number' );
		const [ rangeStart, rangeEnd ] = isArray( filter.value ) ? filter.value : [ filter.value ];

		const rangeStartOnChange = ( newRangeStart ) => {
			onFilterChange( filter.key, 'value', [ newRangeStart, rangeEnd ] );
		};

		const rangeEndOnChange = ( newRangeEnd ) => {
			onFilterChange( filter.key, 'value', [ rangeStart, newRangeEnd ] );
		};

		return interpolateComponents( {
			mixedString: this.getBetweenString(),
			components: {
				rangeStart: this.getFormControl( {
					type: inputType,
					value: rangeStart || '',
					label: sprintf(
						/* eslint-disable-next-line max-len */
						/* translators: Sentence fragment, "range start" refers to the first of two numeric values the field must be between. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
						__( '%(field)s range start', 'woocommerce-admin' ),
						{ field: get( config, [ 'labels', 'add' ] ) }
					),
					onChange: rangeStartOnChange,
					currencySymbol,
					symbolPosition,
				} ),
				rangeEnd: this.getFormControl( {
					type: inputType,
					value: rangeEnd || '',
					label: sprintf(
						/* eslint-disable-next-line max-len */
						/* translators: Sentence fragment, "range end" refers to the second of two numeric values the field must be between. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
						__( '%(field)s range end', 'woocommerce-admin' ),
						{ field: get( config, [ 'labels', 'add' ] ) }
					),
					onChange: rangeEndOnChange,
					currencySymbol,
					symbolPosition,
				} ),
				span: <span className="separator" />,
			},
		} );
	}

	render() {
		const { className, config, filter, onFilterChange, isEnglish } = this.props;
		const { key, rule } = filter;
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
				filter: (
					<div
						className={ classnames( className, 'woocommerce-filters-advanced__input-range', {
							'is-between': 'between' === rule,
						} ) }
					>
						{ this.getFilterInputs() }
					</div>
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

export default NumberFilter;
