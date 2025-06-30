/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { useMemo, useState } from '@wordpress/element';
import clsx from 'clsx';

/**
 * Internal dependencies
 */
import { CheckboxListOptionControl } from './checkbox-list-option-control';
import type { CheckboxListOptions } from './types';
import './style.scss';

export interface CheckboxListProps {
	className?: string | undefined;
	isLoading?: boolean | undefined;
	isDisabled?: boolean | undefined;
	limit?: number | undefined;
	checked?: string[] | undefined;
	onChange: ( value: string ) => void | undefined;
	options?: CheckboxListOptions[] | undefined;
}

/**
 * Component used to show a list of checkboxes in a group.
 *
 * @param {Object}               props            Incoming props for the component.
 * @param {string}               props.className  CSS class used.
 * @param {function(string):any} props.onChange   Function called when inputs change.
 * @param {Array}                props.options    Options for list.
 * @param {Array}                props.checked    Which items are checked.
 * @param {boolean}              props.isLoading  If loading or not.
 * @param {boolean}              props.isDisabled If inputs are disabled or not.
 * @param {number}               props.limit      Whether to limit the number of inputs showing.
 */
const CheckboxList = ( {
	className,
	onChange,
	options = [],
	checked = [],
	isLoading = false,
	isDisabled = false,
	limit = 10,
}: CheckboxListProps ): JSX.Element => {
	const [ showExpanded, setShowExpanded ] = useState( false );

	const placeholder = useMemo( () => {
		return [ ...Array( 5 ) ].map( ( x, i ) => (
			<li
				key={ i }
				style={ {
					/* stylelint-disable */
					width: Math.floor( Math.random() * 75 ) + 25 + '%',
				} }
			>
				{ /* The &nbsp; is required to give the placeholder content and therefore height, without it the placeholder rows do not render */ }
				&nbsp;
			</li>
		) );
	}, [] );

	const renderedShowMore = useMemo( () => {
		const optionCount = options.length;
		const remainingOptionsCount = optionCount - limit;
		return (
			! showExpanded && (
				<li key="show-more" className="show-more">
					<button
						onClick={ () => {
							setShowExpanded( true );
						} }
						aria-expanded={ false }
						aria-label={ sprintf(
							/* translators: %s is referring the remaining count of options */
							_n(
								'Show %s more option',
								'Show %s more options',
								remainingOptionsCount,
								'woocommerce'
							),
							remainingOptionsCount
						) }
					>
						{ sprintf(
							/* translators: %s number of options to reveal. */
							_n(
								'Show %s more',
								'Show %s more',
								remainingOptionsCount,
								'woocommerce'
							),
							remainingOptionsCount
						) }
					</button>
				</li>
			)
		);
	}, [ options, limit, showExpanded ] );

	const renderedShowLess = useMemo( () => {
		return (
			showExpanded && (
				<li key="show-less" className="show-less">
					<button
						onClick={ () => {
							setShowExpanded( false );
						} }
						aria-expanded={ true }
						aria-label={ __( 'Show less options', 'woocommerce' ) }
					>
						{ __( 'Show less', 'woocommerce' ) }
					</button>
				</li>
			)
		);
	}, [ showExpanded ] );

	const renderedOptions = useMemo( () => {
		// Truncate options if > the limit + 5.
		const optionCount = options.length;
		const shouldTruncateOptions = optionCount > limit + 5;
		return (
			<>
				{ options.map( ( option, index ) => (
					<CheckboxListOptionControl
						key={ option.value }
						option={ option }
						shouldTruncateOptions={ shouldTruncateOptions }
						showExpanded={ showExpanded }
						index={ index }
						limit={ limit }
						checked={ checked.includes( option.value ) }
						disabled={ isDisabled }
						renderedShowMore={ renderedShowMore }
						onChange={ onChange }
					/>
				) ) }
				{ shouldTruncateOptions && renderedShowLess }
			</>
		);
	}, [
		options,
		onChange,
		checked,
		showExpanded,
		limit,
		renderedShowLess,
		renderedShowMore,
		isDisabled,
	] );

	const classes = clsx(
		'wc-block-checkbox-list',
		'wc-block-components-checkbox-list',
		{
			'is-loading': isLoading,
		},
		className
	);

	return (
		<ul className={ classes }>
			{ isLoading ? placeholder : renderedOptions }
		</ul>
	);
};

export default CheckboxList;
