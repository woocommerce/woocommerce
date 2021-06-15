/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { PAYMENT_GATEWAYS_STORE_NAME } from '@woocommerce/data';
import { DynamicForm } from '@woocommerce/components';
import { WooPaymentGatewayConnect } from '@woocommerce/onboarding';
import { useSlot } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import sanitizeHTML from '~/lib/sanitize-html';

export const validateFields = ( values, fields ) => {
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

export const Connect = ( {
	markConfigured,
	paymentGateway,
	recordConnectStartEvent,
} ) => {
	const {
		id,
		connectionUrl,
		setupHelpText,
		settingsUrl,
		title,
		requiredSettings: fields,
	} = paymentGateway;

	const { createNotice } = useDispatch( 'core/notices' );
	const { updatePaymentGateway } = useDispatch( PAYMENT_GATEWAYS_STORE_NAME );
	const slot = useSlot( `woocommerce_payment_gateway_connect_${ id }` );
	const hasFills = Boolean( slot?.fills?.length );

	const { isUpdating } = useSelect( ( select ) => {
		const { isPaymentGatewayUpdating } = select(
			PAYMENT_GATEWAYS_STORE_NAME
		);

		return {
			isUpdating: isPaymentGatewayUpdating(),
		};
	} );

	const handleSubmit = ( values ) => {
		recordConnectStartEvent( id );

		updatePaymentGateway( id, {
			enabled: true,
			settings: values,
		} )
			.then( ( result ) => {
				if ( result && result.id === id ) {
					markConfigured( id );
					createNotice(
						'success',
						title +
							__( ' connected successfully', 'woocommerce-admin' )
					);
				}
			} )
			.catch( () => {
				createNotice(
					'error',
					__(
						'There was a problem saving your payment settings',
						'woocommerce-admin'
					)
				);
			} );
	};

	const helpText = setupHelpText && (
		<p dangerouslySetInnerHTML={ sanitizeHTML( setupHelpText ) } />
	);
	const DefaultForm = ( props ) => (
		<DynamicForm
			fields={ fields }
			isBusy={ isUpdating }
			onSubmit={ handleSubmit }
			submitLabel={ __( 'Proceed', 'woocommerce-admin' ) }
			validate={ ( values ) => validateFields( values, fields ) }
			{ ...props }
		/>
	);

	if ( hasFills ) {
		return (
			<WooPaymentGatewayConnect.Slot
				fillProps={ {
					defaultForm: DefaultForm,
					defaultSubmit: handleSubmit,
					defaultFields: fields,
					markConfigured: () => markConfigured( id ),
					paymentGateway,
				} }
				id={ id }
			/>
		);
	}

	if ( connectionUrl ) {
		return (
			<>
				{ helpText }
				<Button isPrimary href={ connectionUrl }>
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
