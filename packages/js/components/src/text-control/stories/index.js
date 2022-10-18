/**
 * External dependencies
 */
import { TextControl } from '@woocommerce/components';
import { useState } from '@wordpress/element';

const Example = () => {
	const [ value, setValue ] = useState( '' );

	return (
		<div>
			<TextControl
				name="text-control"
				label="Enter text here"
				onChange={ ( newValue ) => setValue( newValue ) }
				value={ value }
			/>
			<br />
			<TextControl label="Disabled field" disabled value="" />
		</div>
	);
};

export const Basic = () => <Example />;

export default {
	title: 'WooCommerce Admin/components/TextControl',
	component: TextControl,
};
