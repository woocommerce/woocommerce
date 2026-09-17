/**
 * External dependencies
 */
import { createElement, Component, Fragment } from '@wordpress/element';
import { SelectControl, TextControl } from '@wordpress/components';
import { get, find, isArray } from 'lodash';
import clsx from 'clsx';
import { sprintf, __, _x } from '@wordpress/i18n';
import { CurrencyFactory } from '@woocommerce/currency';
import type { CurrencyConfig } from '@woocommerce/currency';
import type { ComponentType, ReactNode } from 'react';

/**
 * Internal dependencies
 */
import TextControlWithAffixesBase from '../text-control-with-affixes';
import {
	backwardsCompatibleCreateInterpolateElement as createInterpolateElement,
	textContent,
} from './utils';
import type {
	ActiveFilter,
	Currency,
	FilterComponentProps,
	FilterConfig,
	FilterRule,
} from './types';

export type NumberFilterProps = FilterComponentProps & {
	currency: Currency;
};

type TextControlWithAffixesProps = {
	className?: string;
	type?: string;
	value: string | number;
	onChange: ( value: string ) => void;
	prefix?: ReactNode;
	suffix?: ReactNode;
	'aria-label'?: string;
};

// The component is wrapped in withInstanceId, which hides its props from TS.
const TextControlWithAffixes =
	TextControlWithAffixesBase as unknown as ComponentType< TextControlWithAffixesProps >;

type FormControlArgs = {
	type: string;
	value: string | number | null | undefined;
	label: string;
	onChange: ( value: string ) => void;
	currencySymbol: string;
	symbolPosition: string;
};

class NumberFilter extends Component< NumberFilterProps > {
	getBetweenString() {
		return _x(
			'<rangeStart/><span> and </span><rangeEnd/>',
			'Numerical range inputs arranged on a single line',
			'woocommerce'
		);
	}

	getScreenReaderText( filter: ActiveFilter, config: FilterConfig ) {
		const { currency } = this.props;
		const rule: Partial< FilterRule > =
			find( config.rules, { value: filter.rule } ) || {};
		let [ rangeStart, rangeEnd ] = isArray( filter.value )
			? filter.value
			: [ filter.value ];

		// Return nothing if we're missing input(s)
		if ( ! rangeStart || ( rule.value === 'between' && ! rangeEnd ) ) {
			return '';
		}
		const inputType = get( config, [ 'input', 'type' ], 'number' );

		if ( inputType === 'currency' ) {
			const { formatAmount } = CurrencyFactory(
				currency as CurrencyConfig
			);
			rangeStart = formatAmount( rangeStart );
			if ( rangeEnd ) {
				rangeEnd = formatAmount( rangeEnd );
			}
		}

		let filterStr: ReactNode = rangeStart;

		if ( rule.value === 'between' ) {
			filterStr = createInterpolateElement( this.getBetweenString(), {
				rangeStart: <Fragment>{ rangeStart }</Fragment>,
				rangeEnd: <Fragment>{ rangeEnd }</Fragment>,
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

	getFormControl( {
		type,
		value,
		label,
		onChange,
		currencySymbol,
		symbolPosition,
	}: FormControlArgs ) {
		if ( type === 'currency' ) {
			return symbolPosition.indexOf( 'right' ) === 0 ? (
				<TextControlWithAffixes
					suffix={ <span>{ currencySymbol }</span> }
					className="woocommerce-filters-advanced__input"
					type="number"
					value={ value || '' }
					aria-label={ label }
					onChange={ onChange }
				/>
			) : (
				<TextControlWithAffixes
					prefix={ <span>{ currencySymbol }</span> }
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
		const { config, filter, onFilterChange, currency } = this.props;
		const { symbol: currencySymbol, symbolPosition } = currency;

		if ( filter.rule === 'between' ) {
			return this.getRangeInput();
		}
		const inputType = get( config, [ 'input', 'type' ], 'number' );

		const [ rangeStart, rangeEnd ] = isArray( filter.value )
			? filter.value
			: [ filter.value ];
		if ( rangeEnd ) {
			// If there's a value for rangeEnd, we've just changed from "between"
			// to "less than" or "more than" and need to transition the value
			onFilterChange( {
				property: 'value',
				value: rangeStart || rangeEnd,
			} );
		}

		const labelFormat =
			filter.rule === 'lessthan'
				? /* translators: Sentence fragment, "maximum amount" refers to a numeric value the field must be less than. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
				  _x(
						'%(field)s maximum amount',
						'maximum value input',
						'woocommerce'
				  )
				: /* translators: Sentence fragment, "minimum amount" refers to a numeric value the field must be more than. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
				  _x(
						'%(field)s minimum amount',
						'minimum value input',
						'woocommerce'
				  );

		return this.getFormControl( {
			type: inputType,
			value: rangeStart || rangeEnd,
			label: sprintf( labelFormat, {
				field: get( config, [ 'labels', 'add' ] ),
			} ),
			onChange: ( value ) =>
				onFilterChange( { property: 'value', value } ),
			currencySymbol,
			symbolPosition,
		} );
	}

	getRangeInput() {
		const { config, filter, onFilterChange, currency } = this.props;
		const { symbol: currencySymbol, symbolPosition } = currency;
		const inputType = get( config, [ 'input', 'type' ], 'number' );
		const [ rangeStart, rangeEnd ] = isArray( filter.value )
			? filter.value
			: [ filter.value ];

		const rangeStartOnChange = ( newRangeStart: string ) => {
			onFilterChange( {
				property: 'value',
				value: [ newRangeStart, rangeEnd ],
			} );
		};

		const rangeEndOnChange = ( newRangeEnd: string ) => {
			onFilterChange( {
				property: 'value',
				value: [ rangeStart, newRangeEnd ],
			} );
		};

		return createInterpolateElement( this.getBetweenString(), {
			rangeStart: this.getFormControl( {
				type: inputType,
				value: rangeStart || '',
				label: sprintf(
					/* translators: Sentence fragment, "range start" refers to the first of two numeric values the field must be between. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
					__( '%(field)s range start', 'woocommerce' ),
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
					/* translators: Sentence fragment, "range end" refers to the second of two numeric values the field must be between. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
					__( '%(field)s range end', 'woocommerce' ),
					{ field: get( config, [ 'labels', 'add' ] ) }
				),
				onChange: rangeEndOnChange,
				currencySymbol,
				symbolPosition,
			} ),
			span: <span className="separator" />,
		} );
	}

	render() {
		const { className, config, filter, onFilterChange, isEnglish } =
			this.props;
		const { rule } = filter;
		const { labels, rules } = config;

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
					onChange={ ( value ) =>
						onFilterChange( { property: 'rule', value } )
					}
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

		const screenReaderText = this.getScreenReaderText( filter, config );

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

export default NumberFilter;
