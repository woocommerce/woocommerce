/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import React, { useState } from 'react';

/**
 * Internal dependencies
 */
import PhoneNumberInput from '../';
import { validatePhoneNumber } from '../validation';

export default {
	title: 'WooCommerce Admin/components/PhoneNumberInput',
	component: PhoneNumberInput,
};

const PNI: React.FC<
	Partial< React.ComponentPropsWithoutRef< typeof PhoneNumberInput > >
> = ( { children, onChange, ...rest } ) => {
	const [ phone, setPhone ] = useState( '' );

	const handleChange = ( value, i164, country ) => {
		setPhone( value );
		onChange?.( value, i164, country );
	};

	return (
		<>
			<PhoneNumberInput
				{ ...rest }
				value={ phone }
				onChange={ handleChange }
			/>
			{ children }
		</>
	);
};

export const Examples = () => {
	const [ valid, setValid ] = useState( false );

	const handleValidation = ( _, i164, country ) => {
		setValid( validatePhoneNumber( i164, country ) );
	};

	return (
		<>
			<h2>Basic</h2>
			<PNI />
			<h2>Labeled</h2>
			<label htmlFor="pniID">Phone number</label>
			<br />
			<PNI id="pniID" />
			<h2>Validation</h2>
			<PNI onChange={ handleValidation } />
			<br />
			<pre>valid: { valid.toString() }</pre>
			<h2>Custom renders</h2>
			<PNI
				arrowRender={ () => '🔻' }
				itemRender={ ( { name, code } ) => `+${ code }:${ name }` }
				selectedRender={ ( { alpha2, code } ) =>
					`+${ code }:${ alpha2 }`
				}
			/>
		</>
	);
};
