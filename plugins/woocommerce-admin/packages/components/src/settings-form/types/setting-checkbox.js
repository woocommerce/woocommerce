/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';

export const SettingCheckbox = ( { field, ...props } ) => {
	const { label, id, description } = field;

	return (
		<CheckboxControl
			title={ description }
			key={ id }
			label={ label }
			{ ...props }
		/>
	);
};
