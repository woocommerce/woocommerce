/**
 * External dependencies
 */
import React from 'react';
import { useState, createElement } from '@wordpress/element';
import { createRegistry, RegistryProvider } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { store } from '../store';
import { renderField } from '../api';
import { registerCoreProductFields } from '../fields';

const registry = createRegistry();
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
registry.register( store );

registerCoreProductFields();

const fieldConfigs = [
	{
		name: 'text-field',
		type: 'text',
		label: 'Text field',
	},
	{
		name: 'number-field',
		type: 'number',
		label: 'Number field',
	},
	{
		name: 'toggle-field',
		type: 'toggle',
		label: 'Toggle field',
	},
	{
		name: 'checkbox-field',
		type: 'checkbox',
		label: 'Checkbox field',
	},
	{
		name: 'radio-field',
		type: 'radio',
		label: 'Radio field',
		options: [
			{ label: 'Option', value: 'option' },
			{ label: 'Option 2', value: 'option2' },
			{ label: 'Option 3', value: 'option3' },
		],
	},
	{
		name: 'basic-select-control-field',
		type: 'basic-select-control',
		label: 'Basic select control field',
		options: [
			{ label: 'Option', value: 'option' },
			{ label: 'Option 2', value: 'option2' },
			{ label: 'Option 3', value: 'option3' },
		],
	},
];

const RenderField = () => {
	const [ selectedField, setSelectedField ] = useState(
		fieldConfigs[ 0 ].name || undefined
	);
	const [ value, setValue ] = useState();

	const handleChange = ( event ) => {
		setSelectedField( event.target.value );
	};
	const selectedFieldConfig = fieldConfigs.find(
		( f ) => f.name === selectedField
	);
	return (
		<div>
			<select value={ selectedField } onChange={ handleChange }>
				{ fieldConfigs.map( ( field ) => (
					<option key={ field.name } value={ field.name }>
						{ field.label }
					</option>
				) ) }
			</select>
			{ selectedFieldConfig &&
				renderField( selectedFieldConfig.type, {
					value,
					onChange: setValue,
					...selectedFieldConfig,
				} ) }
		</div>
	);
};

export const Basic: React.FC = () => {
	return (
		<RegistryProvider value={ registry }>
			<RenderField />
		</RegistryProvider>
	);
};

export const ToggleWithTooltip: React.FC = () => {
	const [ value, setValue ] = useState();
	return (
		<RegistryProvider value={ registry }>
			{ renderField( 'toggle', {
				value,
				onChange: setValue,
				name: 'toggle',
				label: 'Toggle with Tooltip',
				tooltip: 'This is a sample tooltip',
			} ) }
		</RegistryProvider>
	);
};

export default {
	title: 'WooCommerce Admin/experimental/product-fields',
	component: Basic,
};
