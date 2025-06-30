/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { TextControl } from '../../index';
import { ControlProps } from '../types';

export const TextField = ( {
	field,
	type = 'text',
	...props
}: ControlProps & {
	type?: string;
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
