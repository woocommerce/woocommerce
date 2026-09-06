/**
 * External dependencies
 */
import { createElement, Component, Fragment } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';
import { find, partial } from 'lodash';
import clsx from 'clsx';
import { __, _x } from '@wordpress/i18n';
import { isoDateFormat, toMoment } from '@woocommerce/date';
import moment from 'moment';
import type { Moment } from 'moment';
import type { ReactNode } from 'react';

/**
 * Internal dependencies
 */
import DatePicker from '../calendar/date-picker';
import {
	backwardsCompatibleCreateInterpolateElement as createInterpolateElement,
	textContent,
} from './utils';
import type { FilterComponentProps, FilterConfig, FilterRule } from './types';

const dateStringFormat = __( 'MMM D, YYYY', 'woocommerce' );
const dateFormat = __( 'MM/DD/YYYY', 'woocommerce' );

export type DateFilterProps = FilterComponentProps;

type DateUpdate = {
	date: Moment | null;
	text: string;
	error: string | null;
};

type RangeInput = 'after' | 'before';

type DateFilterState = {
	before: Moment | null;
	beforeText: string;
	beforeError: string | null;
	after: Moment | null;
	afterText: string;
	afterError: string | null;
	rule?: string;
};

class DateFilter extends Component< DateFilterProps, DateFilterState > {
	constructor( props: DateFilterProps ) {
		super( props );
		const { filter } = props;

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
			'<after/><span> and </span><before/>',
			'Date range inputs arranged on a single line',
			'woocommerce'
		);
	}

	getScreenReaderText(
		filterRule: string | undefined,
		config: FilterConfig
	) {
		const rule: Partial< FilterRule > =
			find( config.rules, { value: filterRule } ) || {};

		const { before, after } = this.state;

		// Return nothing if we're missing input(s)
		if ( ! before || ( rule.value === 'between' && ! after ) ) {
			return '';
		}

		let filterStr: ReactNode = before.format( dateStringFormat );

		if ( rule.value === 'between' && after ) {
			filterStr = createInterpolateElement( this.getBetweenString(), {
				after: (
					<Fragment>{ after.format( dateStringFormat ) }</Fragment>
				),
				before: (
					<Fragment>{ before.format( dateStringFormat ) }</Fragment>
				),
				span: <Fragment />,
			} );
		}

		return textContent(
			createInterpolateElement( config.labels.title, {
				filter: <Fragment>{ filterStr }</Fragment>,
				rule: <Fragment>{ rule.label }</Fragment>,
				title: <Fragment />,
			} )
		);
	}

	onSingleDateChange( { date, text, error }: DateUpdate ) {
		const { onFilterChange } = this.props;
		this.setState( { before: date, beforeText: text, beforeError: error } );

		if ( date ) {
			onFilterChange( {
				property: 'value',
				value: date.format( isoDateFormat ),
			} );
		}
	}

	onRangeDateChange( input: RangeInput, { date, text, error }: DateUpdate ) {
		const { onFilterChange } = this.props;

		if ( input === 'after' ) {
			this.setState( {
				after: date,
				afterText: text,
				afterError: error,
			} );
		} else {
			this.setState( {
				before: date,
				beforeText: text,
				beforeError: error,
			} );
		}

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

	onRuleChange( newRule: string ) {
		const { onFilterChange } = this.props;
		const { rule } = this.state;

		const shouldResetValue = [ rule, newRule ].includes( 'between' );

		if ( shouldResetValue ) {
			this.setState( {
				rule: newRule,
				before: null,
				beforeText: '',
				beforeError: null,
				after: null,
				afterText: '',
				afterError: null,
			} );
		} else {
			this.setState( { rule: newRule } );
		}

		onFilterChange( {
			property: 'rule',
			value: newRule,
			shouldResetValue,
		} );
	}

	isFutureDate( date: Date ) {
		return moment().isBefore( moment( date ), 'day' );
	}

	getFormControl( {
		date,
		error,
		onUpdate,
		text,
	}: DateUpdate & { onUpdate: ( update: DateUpdate ) => void } ) {
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
		return createInterpolateElement( this.getBetweenString(), {
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
		const children = createInterpolateElement( labels.title, {
			title: <span className={ className } />,
			rule: (
				<SelectControl
					__next40pxDefaultSize
					className={ clsx(
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
					className={ clsx(
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
		} );

		return (
			<fieldset
				className="woocommerce-filters-advanced__line-item"
				tabIndex={ 0 }
			>
				<legend className="screen-reader-text">
					{ labels.add || '' }
				</legend>
				<div
					className={ clsx(
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
	}
}

export default DateFilter;
