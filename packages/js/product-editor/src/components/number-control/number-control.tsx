/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';
import classNames from 'classnames';
import {
	BaseControl,
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { useNumberInputProps } from '../../hooks/use-number-input-props';
import { Label } from '../label/label';

export type NumberProps = {
	value: string;
	onChange: ( selected: string ) => void;
	label: string;
	suffix?: string;
	help?: string;
	error?: string;
	placeholder?: string;
	onBlur?: () => void;
	required?: boolean;
	tooltip?: string;
};

export const NumberControl: React.FC< NumberProps > = ( {
	value,
	onChange,
	label,
	suffix,
	help,
	error,
	onBlur,
	required,
	tooltip,
	placeholder,
}: NumberProps ) => {
	const inputProps = useNumberInputProps( {
		value: value || '',
		onChange,
	} );

	const id = useInstanceId( BaseControl, 'product_number_field' ) as string;

	return (
		<BaseControl
			className={ classNames( {
				'has-error': error,
			} ) }
			id={ id }
			label={
				<Label
					label={ label }
					required={ required }
					tooltip={ tooltip }
				/>
			}
			help={ error || help }
		>
			<InputControl
				{ ...inputProps }
				id={ id }
				suffix={ suffix }
				placeholder={ placeholder }
				onBlur={ onBlur }
			/>
		</BaseControl>
	);
};
