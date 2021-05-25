/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { DynamicForm, WooRemotePaymentForm } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSlot } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import sanitizeHTML from '~/lib/sanitize-html';

export const PaymentConnect = ( {
	markConfigured,
	paymentGateway,
	recordConnectStartEvent,
} ) => {
	const {
		key,
		oauth_connection_url: oAuthConnectionUrl,
		setup_help_text: setupHelpText,
		required_settings_keys: settingKeys,
		settings,
		settings_url: settingsUrl,
		title,
	} = paymentGateway;

	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { createNotice } = useDispatch( 'core/notices' );
	const slot = useSlot( `woocommerce_remote_payment_form_${ key }` );
	const hasFills = Boolean( slot?.fills?.length );
	const fields = settingKeys
		? settingKeys
				.map( ( settingKey ) => settings[ settingKey ] )
				.filter( Boolean )
		: [];

	const isOptionsRequesting = useSelect( ( select ) => {
		const { isOptionsUpdating } = select( OPTIONS_STORE_NAME );

		return isOptionsUpdating();
	} );

	const updateSettings = async ( values ) => {
		recordConnectStartEvent( key );

		const options = {};

		fields.forEach( ( field ) => {
			const optionName = field.option || field.name;
			options[ optionName ] = values[ field.name ];
		} );

		if ( ! Object.keys( options ).length ) {
			return;
		}

		const update = await updateOptions( {
			...options,
		} );

		if ( update.success ) {
			markConfigured( key );
			createNotice(
				'success',
				title + __( ' connected successfully', 'woocommerce-admin' )
			);
		} else {
			createNotice(
				'error',
				__(
					'There was a problem saving your payment settings',
					'woocommerce-admin'
				)
			);
		}
	};

	const validate = ( values ) => {
		const errors = {};
		const getField = ( fieldId ) =>
			fields.find( ( field ) => field.id === fieldId );

		for ( const [ fieldKey, value ] of Object.entries( values ) ) {
			const field = getField( fieldKey );
			// Matches any word that is capitalized aside from abrevitions like ID.
			const label = field.label.replace( /([A-Z][a-z]+)/g, ( val ) =>
				val.toLowerCase()
			);

			if ( ! ( value || field.type === 'checkbox' ) ) {
				errors[ fieldKey ] = `Please enter your ${ label }`;
			}
		}

		return errors;
	};

	const helpText = setupHelpText && (
		<p dangerouslySetInnerHTML={ sanitizeHTML( setupHelpText ) } />
	);
	const DefaultForm = ( props ) => (
		<DynamicForm
			fields={ fields }
			isBusy={ isOptionsRequesting }
			onSubmit={ updateSettings }
			submitLabel={ __( 'Proceed', 'woocommerce-admin' ) }
			validate={ validate }
			{ ...props }
		/>
	);

	if ( hasFills ) {
		return (
			<WooRemotePaymentForm.Slot
				fillProps={ {
					defaultForm: DefaultForm,
					defaultSubmit: updateSettings,
					defaultFields: fields,
					markConfigured: () => markConfigured( key ),
				} }
				id={ key }
			/>
		);
	}

	if ( oAuthConnectionUrl ) {
		return (
			<>
				{ helpText }
				<Button isPrimary href={ oAuthConnectionUrl }>
					{ __( 'Connect', 'woocommerce-admin' ) }
				</Button>
			</>
		);
	}

	if ( fields.length ) {
		return (
			<>
				{ helpText }
				<DefaultForm />
			</>
		);
	}

	return (
		<>
			{ helpText }
			<Button isPrimary href={ settingsUrl }>
				{ __( 'Manage', 'woocommerce-admin' ) }
			</Button>
		</>
	);
};
