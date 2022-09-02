/**
 * External dependencies
 */
import { CheckboxControl } from '@wordpress/components';
import { createElement } from '@wordpress/element';

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
