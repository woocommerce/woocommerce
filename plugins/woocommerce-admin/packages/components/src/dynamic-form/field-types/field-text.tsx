/**
 * Internal dependencies
 */
import { TextControl } from '../../index';
import { ControlProps } from '../types';

export const TextField: React.FC< ControlProps & { type?: string } > = ( {
	field,
	type = 'text',
	...props
} ) => {
	const { label, description } = field;

	return (
		<TextControl
			type={ type }
			title={ description }
			label={ label }
			{ ...props }
		/>
	);
};
