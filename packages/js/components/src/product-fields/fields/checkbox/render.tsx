/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { BaseProductFieldProps } from '../types';

type CheckboxFieldProps = BaseProductFieldProps< boolean >;

const CheckboxField: React.FC< CheckboxFieldProps > = ( {
	label,
	value,
	onChange,
} ) => {
	return (
		<CheckboxControl
			label={ label }
			onChange={ onChange }
			selected={ value }
		/>
	);
};

export default CheckboxField;
