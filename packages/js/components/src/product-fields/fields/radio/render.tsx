/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { RadioControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { BaseProductFieldProps } from '../types';

type RadioFieldProps = BaseProductFieldProps< string > & {
	options: {
		label: string;
		value: string;
	}[];
};
const RadioField = ( {
	label,
	value,
	onChange,
	options = [],
}: RadioFieldProps ) => {
	return (
		<RadioControl
			label={ label }
			options={ options }
			onChange={ onChange }
			selected={ value }
		/>
	);
};

export default RadioField;
