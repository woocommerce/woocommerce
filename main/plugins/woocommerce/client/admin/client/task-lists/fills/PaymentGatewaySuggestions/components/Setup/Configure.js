/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { PAYMENT_GATEWAYS_STORE_NAME } from '@woocommerce/data';
import { DynamicForm } from '@woocommerce/components';
import { WooPaymentGatewayConfigure } from '@woocommerce/onboarding';
import { useSlot } from '@woocommerce/experimental';
import { recordEvent } from '@woocommerce/tracks';

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

export const Configure = ( { markConfigured, paymentGateway } ) => {
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
	const slot = useSlot( `woocommerce_payment_gateway_configure_${ id }` );
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
		updatePaymentGateway( id, {
			enabled: true,
			settings: values,
		} )
			.then( ( result ) => {
				if ( result && result.id === id ) {
					markConfigured( id );
					createNotice(
						'success',
						sprintf(
							/* translators: %s = title of the payment gateway */
							__( '%s configured successfully', 'woocommerce' ),
							title
						)
					);
				}
			} )
			.catch( () => {
				createNotice(
					'error',
					__(
						'There was a problem saving your payment settings',
						'woocommerce'
					)
				);
			} );
	};

	const helpText = setupHelpText && (
		<p dangerouslySetInnerHTML={ sanitizeHTML( setupHelpText ) } />
	);
	const defaultForm = (
		<DynamicForm
			fields={ fields }
			isBusy={ isUpdating }
			onSubmit={ handleSubmit }
			submitLabel={ __( 'Continue', 'woocommerce' ) }
			validate={ ( values ) => validateFields( values, fields ) }
		/>
	);

	if ( hasFills ) {
		return (
			<WooPaymentGatewayConfigure.Slot
				fillProps={ {
					defaultForm,
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
				<Button
					isPrimary
					onClick={ () =>
						recordEvent( 'tasklist_payment_connect_start', {
							payment_method: id,
						} )
					}
					href={ connectionUrl }
				>
					{ __( 'Connect', 'woocommerce' ) }
				</Button>
			</>
		);
	}

	if ( fields.length ) {
		return (
			<>
				{ helpText }
				{ defaultForm }
			</>
		);
	}

	return (
		<>
			{ helpText || (
				<p>
					{ __(
						'You can manage this payment gateway’s settings by clicking the button below',
						'woocommerce'
					) }
				</p>
			) }
			<Button isPrimary href={ settingsUrl }>
				{ __( 'Get started', 'woocommerce' ) }
			</Button>
		</>
	);
};
