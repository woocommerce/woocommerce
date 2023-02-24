/**
 * External dependencies
 */
import { createElement, Component, Fragment } from '@wordpress/element';
import { SelectControl, TextControl } from '@wordpress/components';
import { get, find, isArray } from 'lodash';
import interpolateComponents from '@automattic/interpolate-components';
import classnames from 'classnames';
import { sprintf, __, _x } from '@wordpress/i18n';

import { CurrencyFactory } from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import TextControlWithAffixes from '../text-control-with-affixes';
import { textContent } from './utils';

class NumberFilter extends Component {
	getBetweenString() {
		return _x(
			'{{rangeStart /}}{{span}} and {{/span}}{{rangeEnd /}}',
			'Numerical range inputs arranged on a single line',
			'woocommerce'
		);
	}

	getScreenReaderText( filter, config ) {
		const { currency } = this.props;
		const rule = find( config.rules, { value: filter.rule } ) || {};
		let [ rangeStart, rangeEnd ] = isArray( filter.value )
			? filter.value
			: [ filter.value ];

		// Return nothing if we're missing input(s)
		if ( ! rangeStart || ( rule.value === 'between' && ! rangeEnd ) ) {
			return '';
		}
		const inputType = get( config, [ 'input', 'type' ], 'number' );

		if ( inputType === 'currency' ) {
			const { formatAmount } = CurrencyFactory( currency );
			rangeStart = formatAmount( rangeStart );
			rangeEnd = formatAmount( rangeEnd );
		}

		let filterStr = rangeStart;

		if ( rule.value === 'between' ) {
			filterStr = interpolateComponents( {
				mixedString: this.getBetweenString(),
				components: {
					rangeStart: <Fragment>{ rangeStart }</Fragment>,
					rangeEnd: <Fragment>{ rangeEnd }</Fragment>,
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

	getFormControl( {
		type,
		value,
		label,
		onChange,
		currencySymbol,
		symbolPosition,
	} ) {
		if ( type === 'currency' ) {
			return symbolPosition.indexOf( 'right' ) === 0 ? (
				<TextControlWithAffixes
					suffix={
						<span
							dangerouslySetInnerHTML={ {
								__html: currencySymbol,
							} }
						/>
					}
					className="woocommerce-filters-advanced__input"
					type="number"
					value={ value || '' }
					aria-label={ label }
					onChange={ onChange }
				/>
			) : (
				<TextControlWithAffixes
					prefix={
						<span
							dangerouslySetInnerHTML={ {
								__html: currencySymbol,
							} }
						/>
					}
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
		if ( Boolean( rangeEnd ) ) {
			// If there's a value for rangeEnd, we've just changed from "between"
			// to "less than" or "more than" and need to transition the value
			onFilterChange( {
				property: 'value',
				value: rangeStart || rangeEnd,
			} );
		}

		let labelFormat = '';

		if ( filter.rule === 'lessthan' ) {
			/* eslint-disable-next-line max-len */
			/* translators: Sentence fragment, "maximum amount" refers to a numeric value the field must be less than. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
			labelFormat = _x(
				'%(field)s maximum amount',
				'maximum value input',
				'woocommerce'
			);
		} else {
			/* eslint-disable-next-line max-len */
			/* translators: Sentence fragment, "minimum amount" refers to a numeric value the field must be more than. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
			labelFormat = _x(
				'%(field)s minimum amount',
				'minimum value input',
				'woocommerce'
			);
		}

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

		const rangeStartOnChange = ( newRangeStart ) => {
			onFilterChange( {
				property: 'value',
				key: [ newRangeStart, rangeEnd ],
			} );
		};

		const rangeEndOnChange = ( newRangeEnd ) => {
			onFilterChange( {
				property: 'value',
				key: [ rangeStart, newRangeEnd ],
			} );
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
						/* eslint-disable-next-line max-len */
						/* translators: Sentence fragment, "range end" refers to the second of two numeric values the field must be between. Screenshot for context: https://cloudup.com/cmv5CLyMPNQ */
						__( '%(field)s range end', 'woocommerce' ),
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
		const { className, config, filter, onFilterChange, isEnglish } =
			this.props;
		const { rule } = filter;
		const { labels, rules } = config;

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
						onChange={ ( value ) =>
							onFilterChange( { property: 'rule', value } )
						}
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

		const screenReaderText = this.getScreenReaderText( filter, config );

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

export default NumberFilter;
