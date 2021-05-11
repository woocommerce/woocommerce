/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SelectControl } from '../../index';

const transformOptions = ( options ) => {
	return Object.keys( options ).reduce( ( all, curr ) => {
		all.push( {
			key: curr,
			label: options[ curr ],
			value: { id: curr },
		} );
		return all;
	}, [] );
};

export const SettingSelect = ( { field, ...props } ) => {
	const { description, id, label, options = {} } = field;

	const transformedOptions = useMemo( () => transformOptions( options ), [
		options,
	] );

	return (
		<SelectControl
			title={ description }
			label={ label }
			key={ id }
			options={ transformedOptions }
			{ ...props }
		/>
	);
};
