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

const CheckboxField = ( { label, value, onChange }: CheckboxFieldProps ) => {
	return (
		<CheckboxControl
			label={ label }
			onChange={ onChange }
			checked={ value }
		/>
	);
};

export default CheckboxField;
