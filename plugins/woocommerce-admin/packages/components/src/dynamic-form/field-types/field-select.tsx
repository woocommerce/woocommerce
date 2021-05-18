/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SelectControl } from '../../index';
import { ControlProps } from '../types';

type SelectControlOption = {
	key: string;
	label: string;
	value: { id: string };
};

const transformOptions = ( options: Record< string, string > ) =>
	Object.entries( options ).map( ( [ key, value ] ) => ( {
		key,
		label: value,
		value: { id: key },
	} ) );

export const SelectField: React.FC< ControlProps > = ( {
	field,
	...props
} ) => {
	const { description, label, options = {} } = field;

	const transformedOptions: SelectControlOption[] = useMemo(
		() => transformOptions( options ),
		[ options ]
	);

	return (
		<SelectControl
			title={ description }
			label={ label }
			options={ transformedOptions }
			{ ...props }
		/>
	);
};
