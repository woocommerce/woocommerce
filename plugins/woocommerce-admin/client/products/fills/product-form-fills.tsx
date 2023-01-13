/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import { ProductForm } from './types';
import { Fields } from './product-form-field-fills';
import { Sections } from './product-form-section-fills';

const Form = () => {
	const [ formData, setFormData ] = useState< ProductForm >();

	useEffect( () => {
		apiFetch< ProductForm >( { path: '/wc-admin/product-form' } ).then(
			( data ) => setFormData( data )
		);
	}, [] );
	return (
		<>
			{ formData && (
				<>
					<Sections sections={ formData.sections } />
					<Fields fields={ formData.fields } />
				</>
			) }
		</>
	);
};

registerPlugin( 'wc-admin-product-editor-form-fills', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: () => {
		return <Form />;
	},
} );
