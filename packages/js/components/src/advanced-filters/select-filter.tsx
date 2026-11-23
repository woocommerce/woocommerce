/**
 * External dependencies
 */
import { createElement, Component, Fragment } from '@wordpress/element';
import { SelectControl, Spinner } from '@wordpress/components';
import { find } from 'lodash';
import clsx from 'clsx';
import { getDefaultOptionValue } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import {
	backwardsCompatibleCreateInterpolateElement as createInterpolateElement,
	textContent,
} from './utils';
import type {
	ActiveFilter,
	FilterComponentProps,
	FilterConfig,
	FilterOption,
	FilterRule,
} from './types';

export type SelectFilterProps = FilterComponentProps;

type SelectFilterState = {
	options?: FilterOption[];
};

class SelectFilter extends Component< SelectFilterProps, SelectFilterState > {
	constructor( props: SelectFilterProps ) {
		super( props );
		const { filter, config, onFilterChange } = props;

		const options = config.input.options;
		this.state = { options };

		this.updateOptions = this.updateOptions.bind( this );

		if ( ! options && config.input.getOptions ) {
			void config.input
				.getOptions()
				.then( this.updateOptions )
				.then( ( returnedOptions ) => {
					if ( ! filter.value ) {
						const value = getDefaultOptionValue(
							config,
							returnedOptions
						);
						onFilterChange( { property: 'value', value } );
					}
				} );
		}
	}

	updateOptions( options: FilterOption[] ) {
		this.setState( { options } );
		return options;
	}

	getScreenReaderText( filter: ActiveFilter, config: FilterConfig ) {
		if ( filter.value === '' ) {
			return '';
		}

		const rule: Partial< FilterRule > =
			find( config.rules, { value: filter.rule } ) || {};
		const value: Partial< FilterOption > =
			find(
				config.input.options,
				( option ) => option.value === filter.value
			) || {};

		return textContent(
			createInterpolateElement( config.labels.title, {
				filter: <Fragment>{ value.label }</Fragment>,
				rule: <Fragment>{ rule.label }</Fragment>,
				title: <Fragment />,
			} )
		);
	}

	render() {
		const { className, config, filter, onFilterChange, isEnglish } =
			this.props;
		const { options } = this.state;
		const { rule, value } = filter;
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
					onChange={ ( selectedValue ) =>
						onFilterChange( {
							property: 'rule',
							value: selectedValue,
						} )
					}
					aria-label={ labels.rule }
				/>
			),
			filter: options ? (
				<SelectControl
					__next40pxDefaultSize
					className={ clsx(
						className,
						'woocommerce-filters-advanced__input'
					) }
					options={ options }
					value={ String( value ?? '' ) }
					onChange={ ( selectedValue ) =>
						onFilterChange( {
							property: 'value',
							value: selectedValue,
						} )
					}
					aria-label={ labels.filter }
				/>
			) : (
				<Spinner />
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

export default SelectFilter;
