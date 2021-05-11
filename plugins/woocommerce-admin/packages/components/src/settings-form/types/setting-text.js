/**
 * Internal dependencies
 */
import { TextControl } from '../../index';

export const SettingText = ( { field, type = 'text', ...props } ) => {
	const { id, label, description } = field;

	return (
		<TextControl
			type={ type }
			title={ description }
			key={ id }
			label={ label }
			{ ...props }
		/>
	);
};
