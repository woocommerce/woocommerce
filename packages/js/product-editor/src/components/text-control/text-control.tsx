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
import { Label } from '../label/label';

export type TextProps = {
	value?: string;
	onChange: ( selected: string ) => void;
	label: string;
	suffix?: string;
	help?: string;
	error?: string;
	placeholder?: string;
	required?: boolean;
	onBlur?: () => void;
	tooltip?: string;
};

export const TextControl: React.FC< TextProps > = ( {
	value,
	onChange,
	label,
	help,
	error,
	onBlur,
	placeholder,
	required,
	tooltip,
}: TextProps ) => {
	const textControlId = useInstanceId(
		BaseControl,
		'text-control'
	) as string;
	return (
		<BaseControl
			id={ textControlId }
			label={
				<Label
					label={ label }
					required={ required }
					tooltip={ tooltip }
				/>
			}
			className={ classNames( {
				'has-error': error,
			} ) }
			help={ error || help }
		>
			<InputControl
				id={ textControlId }
				placeholder={ placeholder }
				value={ value }
				onChange={ onChange }
				onBlur={ onBlur }
			></InputControl>
		</BaseControl>
	);
};
