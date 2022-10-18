/**
 * External dependencies
 */
import { createElement, Component, Fragment } from '@wordpress/element';
import interpolateComponents from '@automattic/interpolate-components';
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

const dateStringFormat = __( 'MMM D, YYYY', 'woocommerce' );
const dateFormat = __( 'MM/DD/YYYY', 'woocommerce' );

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
			rule: filter.rule,
		};

		this.onSingleDateChange = this.onSingleDateChange.bind( this );
		this.onRangeDateChange = this.onRangeDateChange.bind( this );
		this.onRuleChange = this.onRuleChange.bind( this );
	}

	getBetweenString() {
		return _x(
			'{{after /}}{{span}} and {{/span}}{{before /}}',
			'Date range inputs arranged on a single line',
			'woocommerce'
		);
	}

	getScreenReaderText( filterRule, config ) {
		const rule = find( config.rules, { value: filterRule } ) || {};

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
			onFilterChange( {
				property: 'value',
				value: date.format( isoDateFormat ),
			} );
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
				onFilterChange( {
					property: 'value',
					value: [ nextAfter, nextBefore ],
				} );
			}
		}
	}

	onRuleChange( newRule ) {
		const { onFilterChange } = this.props;
		const { rule } = this.state;

		let newDateState = null;
		let shouldResetValue = false;

		if ( [ rule, newRule ].includes( 'between' ) ) {
			newDateState = {
				before: null,
				beforeText: '',
				beforeError: null,
				after: null,
				afterText: '',
				afterError: null,
			};

			shouldResetValue = true;
		}

		this.setState( {
			rule: newRule,
			...newDateState,
		} );

		onFilterChange( {
			property: 'rule',
			value: newRule,
			shouldResetValue,
		} );
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
		const { before, beforeText, beforeError, rule } = this.state;

		if ( rule === 'between' ) {
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
		const { className, config, isEnglish } = this.props;
		const { rule } = this.state;
		const { labels, rules } = config;
		const screenReaderText = this.getScreenReaderText( rule, config );
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
						onChange={ this.onRuleChange }
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
