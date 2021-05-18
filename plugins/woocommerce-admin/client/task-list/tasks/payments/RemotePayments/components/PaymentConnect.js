/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import interpolateComponents from 'interpolate-components';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	Link,
	DynamicForm,
	WooRemotePaymentForm,
	Spinner,
} from '@woocommerce/components';
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSlot, Text } from '@woocommerce/experimental';

export const PaymentConnect = ( {
	markConfigured,
	method,
	recordConnectStartEvent,
} ) => {
	const {
		api_details_url: apiDetailsUrl,
		fields: fieldsConfig,
		key,
		title,
	} = method;

	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { createNotice } = useDispatch( 'core/notices' );
	const slot = useSlot( `woocommerce_remote_payment_form_${ key }` );
	const hasFills = Boolean( slot?.fills?.length );
	const [ state, setState ] = useState( 'loading' );
	const [ fields, setFields ] = useState( null );

	// This transform will be obsolete when we can derive essential fields from the API
	const settingsTransform = ( settings ) => {
		const essentialFields = fieldsConfig.map( ( field ) => field.name );

		return Object.values( settings ).filter( ( setting ) =>
			essentialFields.includes( setting.id )
		);
	};

	// TODO: Will soon be replaced with the payments data store implemented in #6918
	useEffect( () => {
		apiFetch( {
			path: `/wc/v3/payment_gateways/${ key }/`,
		} )
			.then( ( results ) => {
				setFields( settingsTransform( results.settings ) );
				setState( 'loaded' );
			} )
			.catch( ( e ) => {
				setState( 'error' );
				/* eslint-disable no-console */
				console.error(
					`Error fetching information for payment gateway ${ key }`,
					e.message
				);
				/* eslint-enable no-console */
			} );
	}, [] );

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

	const helpText = interpolateComponents( {
		mixedString: __(
			'Your API details can be obtained from your {{link/}}',
			'woocommerce-admin'
		),
		components: {
			link: (
				<Link href={ apiDetailsUrl } target="_blank" type="external">
					{ sprintf( __( '%(title)s account', 'woocommerce-admin' ), {
						title,
					} ) }
				</Link>
			),
		},
	} );

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

	if ( state === 'error' ) {
		return (
			<Text>
				{
					( __( 'There was an error loading the payment fields' ),
					'woocommerce-admin' )
				}
			</Text>
		);
	}

	if ( state === 'loading' ) {
		return <Spinner />;
	}

	return (
		<>
			{ hasFills ? (
				<WooRemotePaymentForm.Slot
					fillProps={ {
						defaultForm: DefaultForm,
						defaultSubmit: updateSettings,
						defaultFields: fields,
						markConfigured: () => markConfigured( key ),
					} }
					id={ key }
				/>
			) : (
				<>
					<DefaultForm />
					<p>{ helpText }</p>
				</>
			) }
		</>
	);
};
