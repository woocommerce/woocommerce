/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import classNames from 'classnames';
import {
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Label } from '../label/label';
import { TextControlProps } from './types';

export function TextControl( {
	label,
	help,
	error,
	tooltip,
	className,
	required,
	onChange,
	onBlur,
	...props
}: TextControlProps ) {
	return (
		<InputControl
			{ ...props }
			className={ classNames( className, {
				'has-error': error,
			} ) }
			label={
				<Label
					label={ label }
					required={ required }
					tooltip={ tooltip }
				/>
			}
			required={ required }
			help={ error || help }
			onChange={ onChange }
			onBlur={ onBlur }
		/>
	);
}
