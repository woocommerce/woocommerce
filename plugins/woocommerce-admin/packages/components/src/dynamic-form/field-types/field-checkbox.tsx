/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { ControlProps } from '../types';

export const CheckboxField: React.FC< ControlProps > = ( {
	field,
	onChange,
	...props
} ) => {
	const { label, description } = field;

	return (
		<CheckboxControl
			onChange={ ( val ) => onChange( val ) }
			title={ description }
			label={ label }
			{ ...props }
		/>
	);
};
