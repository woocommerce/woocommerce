/**
 * External dependencies
 */
import React from 'react';
import { useState, createElement } from '@wordpress/element';
import { createRegistry, RegistryProvider, select } from '@wordpress/data';
import {
	// @ts-expect-error `__experimentalInputControl` does exist.
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { store } from '../store';
import { registerProductField, renderField } from '../api';

const registry = createRegistry();
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet.
registry.register( store );

registerProductField( 'text', {
	name: 'text',
	edit: ( props ) => {
		return <InputControl type="text" { ...props } />;
	},
} );

registerProductField( 'number', {
	name: 'number',
	edit: () => {
		return <InputControl type="number" />;
	},
} );

const RenderField = () => {
	const [ selectedField, setSelectedField ] = useState();

	const fields: string[] = select( store ).getRegisteredProductFields();
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
			{ renderField( selectedField || fields[ 0 ], { name: 'test' } ) }
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
