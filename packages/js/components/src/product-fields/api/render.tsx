/**
 * External dependencies
 */
import { select } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { Controller, Control } from 'react-hook-form';
import {
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';
/**
 * Internal dependencies
 */
import { store as productFieldStore } from '../store';
import { ProductFieldDefinition } from '../store/types';

export function renderField(
	name: string,
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	props: Record< string, any >,
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	control: Control< any, any >
) {
	const fieldConfig: ProductFieldDefinition =
		select( productFieldStore ).getProductField( name );

	if ( fieldConfig.render ) {
		const RenderComponent = fieldConfig.render;
		return (
			<Controller
				control={ control }
				name={ props.name }
				render={ ( { field } ) => (
					<RenderComponent
						onChange={ field.onChange }
						value={ field.value }
						{ ...props }
					/>
				) }
			></Controller>
		);
	}
	if ( fieldConfig.type ) {
		return (
			<Controller
				name={ props.name }
				render={ ( { field } ) => (
					<InputControl
						type={ fieldConfig.type }
						{ ...field }
						{ ...props }
					/>
				) }
			></Controller>
		);
	}
	return null;
}
