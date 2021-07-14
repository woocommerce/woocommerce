/**
 * External dependencies
 */
import { createElement, Component, Fragment } from '@wordpress/element';
import interpolateComponents from 'interpolate-components';
import { SelectControl } from '@wordpress/components';
import { find, partial } from 'lodash';
import classnames from 'classnames';
import { __, _x } from '@wordpress/i18n';
import { isoDateFormat, toMoment } from '@woocommerce/date';
import moment from 'moment';

/**
 * Internal dependencies
 */
import DatePicker from '../calendar/date-picker';
import { textContent } from './utils';

const dateStringFormat = __( 'MMM D, YYYY', 'woocommerce-admin' );
const dateFormat = __( 'MM/DD/YYYY', 'woocommerce-admin' );

class DateFilter extends Component {
	constructor( { filter } ) {
		super( ...arguments );

		const [ isoAfter, isoBefore ] = Array.isArray( filter.value )
			? filter.value
			: [ null, filter.value ];
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
		if ( ! before || ( rule.value === 'between' && ! after ) ) {
			return '';
		}

		let filterStr = before.format( dateStringFormat );

		if ( rule.value === 'between' ) {
			filterStr = interpolateComponents( {
				mixedString: this.getBetweenString(),
				components: {
					after: (
						<Fragment>
							{ after.format( dateStringFormat ) }
						</Fragment>
					),
					before: (
						<Fragment>
							{ before.format( dateStringFormat ) }
						</Fragment>
					),
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
					title: <Fragment />,
				},
			} )
		);
	}

	onSingleDateChange( { date, text, error } ) {
		const { onFilterChange } = this.props;
		this.setState( { before: date, beforeText: text, beforeError: error } );

		if ( date ) {
			onFilterChange( 'value', date.format( isoDateFormat ) );
		}
	}

	onRangeDateChange( input, { date, text, error } ) {
		const { onFilterChange } = this.props;

		this.setState( {
			[ input ]: date,
			[ input + 'Text' ]: text,
			[ input + 'Error' ]: error,
		} );

		if ( date ) {
			const { before, after } = this.state;
			let nextAfter = null;
			let nextBefore = null;

			if ( input === 'after' ) {
				nextAfter = date.format( isoDateFormat );
				nextBefore = before ? before.format( isoDateFormat ) : null;
			}

			if ( input === 'before' ) {
				nextAfter = after ? after.format( isoDateFormat ) : null;
				nextBefore = date.format( isoDateFormat );
			}

			if ( nextAfter && nextBefore ) {
				onFilterChange( 'value', [ nextAfter, nextBefore ] );
			}
		}
	}

	isFutureDate( dateString ) {
		return moment().isBefore( moment( dateString ), 'day' );
	}

	getFormControl( { date, error, onUpdate, text } ) {
		return (
			<DatePicker
				date={ date }
				dateFormat={ dateFormat }
				error={ error }
				isInvalidDate={ this.isFutureDate }
				onUpdate={ onUpdate }
				text={ text }
			/>
		);
	}

	getRangeInput() {
		const {
			before,
			beforeText,
			beforeError,
			after,
			afterText,
			afterError,
		} = this.state;
		return interpolateComponents( {
			mixedString: this.getBetweenString(),
			components: {
				after: this.getFormControl( {
					date: after,
					error: afterError,
					onUpdate: partial( this.onRangeDateChange, 'after' ),
					text: afterText,
				} ),
				before: this.getFormControl( {
					date: before,
					error: beforeError,
					onUpdate: partial( this.onRangeDateChange, 'before' ),
					text: beforeText,
				} ),
				span: <span className="separator" />,
			},
		} );
	}

	getFilterInputs() {
		const { filter } = this.props;
		const { before, beforeText, beforeError } = this.state;

		if ( filter.rule === 'between' ) {
			return this.getRangeInput();
		}

		return this.getFormControl( {
			date: before,
			error: beforeError,
			onUpdate: this.onSingleDateChange,
			text: beforeText,
		} );
	}

	render() {
		const {
			className,
			config,
			filter,
			isEnglish,
			onFilterChange,
		} = this.props;
		const { rule } = filter;
		const { labels, rules } = config;
		const screenReaderText = this.getScreenReaderText( filter, config );
		const children = interpolateComponents( {
			mixedString: labels.title,
			components: {
				title: <span className={ className } />,
				rule: (
					<SelectControl
						className={ classnames(
							className,
							'woocommerce-filters-advanced__rule'
						) }
						options={ rules }
						value={ rule }
						onChange={ partial( onFilterChange, 'rule' ) }
						aria-label={ labels.rule }
					/>
				),
				filter: (
					<div
						className={ classnames(
							className,
							'woocommerce-filters-advanced__input-range',
							{
								'is-between': rule === 'between',
							}
						) }
					>
						{ this.getFilterInputs() }
					</div>
				),
			},
		} );
		/*eslint-disable jsx-a11y/no-noninteractive-tabindex*/
		return (
			<fieldset
				className="woocommerce-filters-advanced__line-item"
				tabIndex="0"
			>
				<legend className="screen-reader-text">
					{ labels.add || '' }
				</legend>
				<div
					className={ classnames(
						'woocommerce-filters-advanced__fieldset',
						{
							'is-english': isEnglish,
						}
					) }
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

export default DateFilter;
