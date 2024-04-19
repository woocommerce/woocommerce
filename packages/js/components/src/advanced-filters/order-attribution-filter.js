/**
 * External dependencies
 */
import { createElement, Component, Fragment } from '@wordpress/element';
import { SelectControl, Spinner } from '@wordpress/components';
import { find } from 'lodash';
import PropTypes from 'prop-types';
import classnames from 'classnames';
import { getDefaultOptionValue } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import {
	backwardsCompatibleCreateInterpolateElement as createInterpolateElement,
	textContent,
} from './utils';

class OrderAttributionFilter123 extends Component {
	constructor( { filter, config, onFilterChange } ) {
		super( ...arguments );

		const options = config.input.options;
		this.state = { options };

		this.updateOptions = this.updateOptions.bind( this );

		if ( ! options && config.input.getOptions ) {
			config.input
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

	updateOptions( options ) {
		this.setState( { options } );
		return options;
	}

	getScreenReaderText( filter, config ) {
		if ( filter.value === '' ) {
			return '';
		}

		const rule = find( config.rules, { value: filter.rule } ) || {};
		const value =
			find( config.input.options, { value: filter.value } ) || {};

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
					className={ classnames(
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
					className={ classnames(
						className,
						'woocommerce-filters-advanced__input'
					) }
					options={ options }
					value={ value }
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

/// =====

const getScreenReaderText = ( filter, config ) => {
	if ( filter.value === '' ) {
		return '';
	}

	const rule = find( config.rules, { value: filter.rule } ) || {};
	const value = find( config.input.options, { value: filter.value } ) || {};

	return textContent(
		createInterpolateElement( config.labels.title, {
			filter: <Fragment>{ value.label }</Fragment>,
			rule: <Fragment>{ rule.label }</Fragment>,
			title: <Fragment />,
		} )
	);
};

const OrderAttributionFilter = ( {
	className,
	config,
	filter,
	onFilterChange,
	isEnglish,
} ) => {
	const { labels, rules } = config;
	const { rule, value } = filter;
	const screenReaderText = getScreenReaderText( filter, config );

	// TODO: call API to get options.
	const options = [
		{
			value: 'direct',
			label: 'Direct',
		},
	];

	const children = createInterpolateElement( labels.title, {
		title: <span className={ className } />,
		rule: (
			<SelectControl
				className={ classnames(
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
				className={ classnames(
					className,
					'woocommerce-filters-advanced__input'
				) }
				options={ options }
				value={ value }
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

	return (
		<fieldset
			className="woocommerce-filters-advanced__line-item"
			tabIndex="0"
		>
			<legend className="screen-reader-text">{ labels.add || '' }</legend>
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
				<span className="screen-reader-text">{ screenReaderText }</span>
			) }
		</fieldset>
	);
};

OrderAttributionFilter.propTypes = {
	/**
	 * The configuration object for the single filter to be rendered.
	 */
	config: PropTypes.shape( {
		labels: PropTypes.shape( {
			rule: PropTypes.string,
			title: PropTypes.string,
			filter: PropTypes.string,
		} ),
		rules: PropTypes.arrayOf( PropTypes.object ),
		input: PropTypes.object,
	} ).isRequired,
	/**
	 * The activeFilter handed down by AdvancedFilters.
	 */
	filter: PropTypes.shape( {
		key: PropTypes.string,
		rule: PropTypes.string,
		value: PropTypes.string,
	} ).isRequired,
	/**
	 * Function to be called on update.
	 */
	onFilterChange: PropTypes.func.isRequired,
};

export default OrderAttributionFilter;
