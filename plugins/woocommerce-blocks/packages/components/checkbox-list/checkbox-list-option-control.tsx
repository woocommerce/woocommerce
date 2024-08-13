/**
 * External dependencies
 */
import { useInstanceId } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { CheckboxControl } from '../checkbox-control';
import type { CheckboxListOptions } from './types';

export type CheckboxListOptionControlProps = {
	option: CheckboxListOptions;
	shouldTruncateOptions: boolean;
	showExpanded: boolean;
	index: number;
	limit: number;
	checked: boolean;
	disabled: boolean;
	renderedShowMore: false | JSX.Element;
	onChange: ( value: string ) => void;
};

export function CheckboxListOptionControl( {
	option,
	shouldTruncateOptions,
	showExpanded,
	index,
	limit,
	checked,
	disabled,
	renderedShowMore,
	onChange,
}: CheckboxListOptionControlProps ) {
	const checkboxControlInstanceId = useInstanceId(
		CheckboxListOptionControl,
		'wc-block-checkbox-list-option'
	) as string;

	return (
		<>
			<li
				{ ...( shouldTruncateOptions &&
					! showExpanded &&
					index >= limit && { hidden: true } ) }
			>
				<CheckboxControl
					id={ checkboxControlInstanceId }
					className="wc-block-checkbox-list__checkbox"
					label={ option.label }
					checked={ checked }
					value={ option.value }
					onChange={ () => {
						onChange( option.value );
					} }
					disabled={ disabled }
				/>
			</li>
			{ shouldTruncateOptions && index === limit - 1 && renderedShowMore }
		</>
	);
}
