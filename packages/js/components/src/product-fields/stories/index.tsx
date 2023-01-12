/**
 * External dependencies
 */
import React from 'react';
import { useState, createElement } from '@wordpress/element';
import { createRegistry, RegistryProvider, select } from '@wordpress/data';

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

const RenderField = () => {
	const fields: string[] = select( store ).getRegisteredProductFields();
	const [ selectedField, setSelectedField ] = useState(
		fields ? fields[ 0 ] : undefined
	);
	const [ value, setValue ] = useState();

	const handleChange = ( event ) => {
		setSelectedField( event.target.value );
	};
	return (
		<div>
			<select value={ selectedField } onChange={ handleChange }>
				{ fields.map( ( field ) => (
					<option key={ field } value={ field }>
						{ field }
					</option>
				) ) }
			</select>
			{ selectedField &&
				renderField( selectedField, {
					value,
					name: 'test',
					label: 'Test field',
					onChange: setValue,
					options: [
						{ label: 'Option', value: 'option' },
						{ label: 'Option 2', value: 'option2' },
						{ label: 'Option 3', value: 'option3' },
					],
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

export default {
	title: 'WooCommerce Admin/experimental/product-fields',
	component: Basic,
};
