/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import interpolateComponents from 'interpolate-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { Form, Link, TextControl } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

export const PaymentConnect = ( {
	markConfigured,
	method,
	recordConnectStartEvent,
} ) => {
	const { api_details_url: apiDetailsUrl, fields, key, title } = method;
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { createNotice } = useDispatch( 'core/notices' );

	const isOptionsRequesting = useSelect( ( select ) => {
		const { isOptionsUpdating } = select( OPTIONS_STORE_NAME );

		return isOptionsUpdating();
	} );

	const getInitialConfigValues = () => {
		if ( fields ) {
			return fields.reduce( ( data, field ) => {
				return {
					...data,
					[ field.name ]: '',
				};
			}, {} );
		}
	};

	const validate = ( values ) => {
		if ( fields ) {
			return fields.reduce( ( errors, field ) => {
				if ( ! values[ field.name ] ) {
					// Matches any word that is capitalized aside from abrevitions like ID.
					const label = field.label.replace(
						/([A-Z][a-z]+)/,
						( val ) => val.toLowerCase()
					);
					return {
						...errors,
						[ field.name ]: __( 'Please enter your ' ) + label,
					};
				}
				return errors;
			}, {} );
		}
		return {};
	};

	const updateSettings = async ( values ) => {
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

	return (
		<Form
			initialValues={ getInitialConfigValues() }
			onSubmitCallback={ updateSettings }
			validate={ validate }
		>
			{ ( { getInputProps, handleSubmit } ) => {
				return (
					<>
						{ ( fields || [] ).map( ( field ) => (
							<TextControl
								key={ field.name }
								label={ field.label }
								required
								{ ...getInputProps( field.name ) }
							/>
						) ) }

						<Button
							isPrimary
							isBusy={ isOptionsRequesting }
							onClick={ ( event ) => {
								recordConnectStartEvent( key );
								handleSubmit( event );
							} }
						>
							{ __( 'Proceed', 'woocommerce-admin' ) }
						</Button>

						<p>{ helpText }</p>
					</>
				);
			} }
		</Form>
	);
};
