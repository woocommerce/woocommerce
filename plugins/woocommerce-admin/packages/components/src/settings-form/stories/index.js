/**
 * External dependencies
 */
import { SettingsForm } from '@woocommerce/components';
import { useState } from '@wordpress/element';

const fields = [
	{
		id: 'user_name',
		label: 'Username',
		description: 'This is your username.',
		type: 'text',
		value: '',
		default: '',
		tip: 'This is your username.',
		placeholder: '',
	},
	{
		id: 'pass_phrase',
		label: 'Passphrase',
		description:
			'* Required. Needed to ensure the data passed through is secure.',
		type: 'password',
		value: '',
		default: '',
		tip: '* Required. Needed to ensure the data passed through is secure.',
		placeholder: '',
	},
	{
		id: 'button_type',
		label: 'Button Type',
		description: 'Select the button type you would like to show.',
		type: 'select',
		value: 'buy',
		default: 'buy',
		tip: 'Select the button type you would like to show.',
		placeholder: '',
		options: {
			default: 'Default',
			buy: 'Buy',
			donate: 'Donate',
			branded: 'Branded',
			custom: 'Custom',
		},
	},
	{
		id: 'checkbox_sample',
		label: 'Checkbox style',
		description: 'This is an example checkbox field.',
		type: 'checkbox',
		value: 'yes',
		default: 'yes',
		tip: 'This is an example checkbox field.',
		placeholder: '',
	},
];

const getField = ( fieldId ) =>
	fields.find( ( field ) => field.id === fieldId );

const validate = ( values ) => {
	const errors = {};

	for ( const [ key, value ] of Object.entries( values ) ) {
		const field = getField( key );

		if ( ! value ) {
			errors[ key ] =
				field.type === 'checkbox'
					? 'This is required'
					: `Please enter your ${ field.label.toLowerCase() }`;
		}
	}

	return errors;
};

const SettingsExample = () => {
	const [ submitted, setSubmitted ] = useState( null );
	return (
		<>
			<SettingsForm
				fields={ fields }
				onSubmit={ ( values ) => setSubmitted( values ) }
				validate={ validate }
			/>
			<h4>Submitted:</h4>
			<p>{ submitted ? JSON.stringify( submitted, null, 3 ) : 'None' }</p>
		</>
	);
};

export const Basic = () => <SettingsExample />;

export default {
	title: 'WooCommerce Admin/components/SettingsForm',
	component: SettingsForm,
};
