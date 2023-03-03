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
const RadioField: React.FC< RadioFieldProps > = ( {
	label,
	value,
	onChange,
	options = [],
} ) => {
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
