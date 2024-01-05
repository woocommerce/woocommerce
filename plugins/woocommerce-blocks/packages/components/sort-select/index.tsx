/**
 * External dependencies
 */
import classNames from 'classnames';
import { withInstanceId } from '@wordpress/compose';
import type { ChangeEventHandler } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';
import Label from '../label'; // Imported like this because importing from the components package loads the data stores unnecessarily - not a problem in the front end but would require a lot of unit test rewrites to prevent breaking tests due to incorrect mocks.

export interface SortSelectProps {
	/**
	 * Unique id for component instance.
	 */
	instanceId: number;
	/**
	 * CSS class used.
	 */
	className?: string;
	/**
	 * Label for the select.
	 */
	label?: string;
	/**
	 * Function to call on the change event.
	 */
	onChange: ChangeEventHandler< HTMLSelectElement >;
	/**
	 * Option values for the select.
	 */
	options: {
		key: string;
		label: string;
	}[];
	/**
	 * Screen reader label.
	 */
	screenReaderLabel: string;
	/**
	 * The selected value.
	 */
	value?: string;

	/**
	 * Whether the select is read only.
	 */
	readOnly?: boolean;
}

/**
 * Component used for 'Order by' selectors, which renders a label
 * and a <select> with the options provided in the props.
 */
export const SortSelect = ( {
	className,
	instanceId,
	label = '',
	onChange,
	options,
	screenReaderLabel,
	value = '',
	readOnly = false,
}: SortSelectProps ): JSX.Element => {
	const selectId = `wc-block-components-sort-select__select-${ instanceId }`;

	return (
		<div
			className={ classNames(
				'wc-block-sort-select',
				'wc-block-components-sort-select',
				className
			) }
		>
			<Label
				label={ label }
				screenReaderLabel={ screenReaderLabel }
				wrapperElement="label"
				wrapperProps={ {
					className:
						'wc-block-sort-select__label wc-block-components-sort-select__label',
					htmlFor: selectId,
				} }
			/>
			<select
				disabled={ !! readOnly }
				id={ selectId }
				className="wc-block-sort-select__select wc-block-components-sort-select__select"
				onChange={ onChange }
				value={ value }
			>
				{ options &&
					options.map( ( option ) => (
						<option key={ option.key } value={ option.key }>
							{ option.label }
						</option>
					) ) }
			</select>
		</div>
	);
};

export default withInstanceId( SortSelect );
