/** @format */
/**
 * External dependencies
 */
import { Component, Fragment } from '@wordpress/element';
import interpolateComponents from 'interpolate-components';
import { SelectControl } from '@wordpress/components';
import { find, partial } from 'lodash';
import classnames from 'classnames';
import { __, _x } from '@wordpress/i18n';

/**
 * WooCommerce dependencies
 */
import { isoDateFormat, toMoment } from '@woocommerce/date';

/**
 * Internal dependencies
 */
import DatePicker from '../../calendar/date-picker';
import { textContent } from './utils';
import moment from 'moment';

const dateStringFormat = __( 'MMM D, YYYY', 'woocommerce-admin' );
const dateFormat = __( 'MM/DD/YYYY', 'woocommerce-admin' );

class DateFilter extends Component {
	constructor( { filter } ) {
		super( ...arguments );

		const [ isoAfter, isoBefore ] = Array.isArray( filter.value ) ? filter.value : [ null, filter.value ];
		const after = isoAfter ? toMoment( isoDateFormat, isoAfter ) : null;
		const before = isoBefore ? toMoment( isoDateFormat, isoBefore ) : null;

		this.state = {
			before,
			beforeText: before ? before.format( dateFormat ) : '',
			beforeError: null,
			after,
			afterText: after ? after.format( dateFormat ) : '',
			afterError: null,
		};

		this.onSingleDateChange = this.onSingleDateChange.bind( this );
		this.onRangeDateChange = this.onRangeDateChange.bind( this );
		this.onRuleChange = this.onRuleChange.bind( this );
	}

	getBetweenString() {
		return _x(
			'{{after /}}{{span}} and {{/span}}{{before /}}',
			'Date range inputs arranged on a single line',
			'woocommerce-admin'
		);
	}

	getScreenReaderText( filter, config ) {
		const rule = find( config.rules, { value: filter.rule } ) || {};

		const { before, after } = this.state;

		// Return nothing if we're missing input(s)
		if ( ! before || ( 'between' === rule.value && ! after ) ) {
			return '';
		}

		let filterStr = before.format( dateStringFormat );

		if ( 'between' === rule.value ) {
			filterStr = interpolateComponents( {
				mixedString: this.getBetweenString(),
				components: {
					after: <Fragment>{ after.format( dateStringFormat ) }</Fragment>,
					before: <Fragment>{ before.format( dateStringFormat ) }</Fragment>,
					span: <Fragment />,
				},
			} );
		}

		return textContent(
			interpolateComponents( {
				mixedString: config.labels.title,
				components: {
					filter: <Fragment>{ filterStr }</Fragment>,
					rule: <Fragment>{ rule.label }</Fragment>,
				},
			} )
		);
	}

	onSingleDateChange( { date, text, error } ) {
		const { filter, onFilterChange } = this.props;
		this.setState( { before: date, beforeText: text, beforeError: error } );

		if ( date ) {
			onFilterChange( filter.key, 'value', date.format( isoDateFormat ) );
		}
	}

	onRangeDateChange( input, { date, text, error } ) {
		const { filter, onFilterChange } = this.props;

		this.setState( {
			[ input ]: date,
			[ input + 'Text' ]: text,
			[ input + 'Error' ]: error,
		} );

		if ( date ) {
			const { before, after } = this.state;
			let nextAfter = null;
			let nextBefore = null;

			if ( 'after' === input ) {
				nextAfter = date.format( isoDateFormat );
				nextBefore = before ? before.format( isoDateFormat ) : null;
			}

			if ( 'before' === input ) {
				nextAfter = after ? after.format( isoDateFormat ) : null;
				nextBefore = date.format( isoDateFormat );
			}

			if ( nextAfter && nextBefore ) {
				onFilterChange( filter.key, 'value', [ nextAfter, nextBefore ] );
			}
		}
	}

	isFutureDate( dateString ) {
		return moment().isBefore( moment( dateString ), 'day' );
	}

	getFilterInputs() {
		const { filter } = this.props;
		const { before, beforeText, beforeError, after, afterText, afterError } = this.state;

		if ( 'between' === filter.rule ) {
			return interpolateComponents( {
				mixedString: this.getBetweenString(),
				components: {
					after: (
						<DatePicker
							date={ after }
							text={ afterText }
							error={ afterError }
							onUpdate={ partial( this.onRangeDateChange, 'after' ) }
							dateFormat={ dateFormat }
							isInvalidDate={ this.isFutureDate }
						/>
					),
					before: (
						<DatePicker
							date={ before }
							text={ beforeText }
							error={ beforeError }
							onUpdate={ partial( this.onRangeDateChange, 'before' ) }
							dateFormat={ dateFormat }
							isInvalidDate={ this.isFutureDate }
						/>
					),
					span: <span className="separator" />,
				},
			} );
		}

		return (
			<DatePicker
				date={ before }
				text={ beforeText }
				error={ beforeError }
				onUpdate={ this.onSingleDateChange }
				dateFormat={ dateFormat }
				isInvalidDate={ this.isFutureDate }
			/>
		);
	}

	onRuleChange( value ) {
		const { onFilterChange, filter, updateFilter } = this.props;
		const { before } = this.state;
		if ( 'between' === filter.rule && 'between' !== value ) {
			updateFilter( {
				key: filter.key,
				rule: value,
				value: before ? before.format( isoDateFormat ) : undefined,
			} );
		} else {
			onFilterChange( filter.key, 'rule', value );
		}
	}

	render() {
		const { className, config, filter, isEnglish } = this.props;
		const { rule } = filter;
		const { labels, rules } = config;
		const screenReaderText = this.getScreenReaderText( filter, config );
		const children = interpolateComponents( {
			mixedString: labels.title,
			components: {
				title: <span className={ className } />,
				rule: (
					<SelectControl
						className={ classnames( className, 'woocommerce-filters-advanced__rule' ) }
						options={ rules }
						value={ rule }
						onChange={ this.onRuleChange }
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
		/*eslint-disable jsx-a11y/no-noninteractive-tabindex*/
		return (
			<fieldset className="woocommerce-filters-advanced__line-item" tabIndex="0">
				<legend className="screen-reader-text">{ labels.add || '' }</legend>
				<div
					className={ classnames( 'woocommerce-filters-advanced__fieldset', {
						'is-english': isEnglish,
					} ) }
				>
					{ children }
				</div>
				{ screenReaderText && <span className="screen-reader-text">{ screenReaderText }</span> }
			</fieldset>
		);
		/*eslint-enable jsx-a11y/no-noninteractive-tabindex*/
	}
}

export default DateFilter;
