/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import interpolateComponents from 'interpolate-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { Form, Link, Stepper, TextControl } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

export function GenericPaymentStep( {
	installStep,
	markConfigured,
	methodConfig,
	recordConnectStartEvent,
} ) {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { createNotice } = useDispatch( 'core/notices' );

	const isOptionsRequesting = useSelect( ( select ) => {
		const { isOptionsUpdating } = select( OPTIONS_STORE_NAME );

		return isOptionsUpdating();
	} );

	const getInitialConfigValues = () => {
		if ( methodConfig && methodConfig.fields ) {
			return methodConfig.fields.reduce( ( data, field ) => {
				return {
					...data,
					[ field.name ]: '',
				};
			}, {} );
		}
	};

	const validate = ( values ) => {
		if ( methodConfig && methodConfig.fields ) {
			return methodConfig.fields.reduce( ( errors, field ) => {
				if ( ! values[ field.name ] ) {
					// Matches any word that is capitalized aside from abrevitions like ID.
					const title = field.title.replace(
						/([A-Z][a-z]+)/,
						( val ) => val.toLowerCase()
					);
					return {
						...errors,
						[ field.name ]: __( 'Please enter your ' ) + title,
					};
				}
				return errors;
			}, {} );
		}
		return {};
	};

	const updateSettings = async ( values ) => {
		// Because the GenericPaymentStep extension only works with the South African Rand
		// currency, force the store to use it while setting the GenericPaymentStep settings
		const options = methodConfig.getOptions
			? methodConfig.getOptions( values )
			: null;
		if ( ! options ) {
			return;
		}
		const update = await updateOptions( {
			...options,
		} );

		if ( update.success ) {
			markConfigured( methodConfig.key );
			createNotice(
				'success',
				methodConfig.title +
					__( ' connected successfully', 'woocommerce-admin' )
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

	const renderConnectStep = () => {
		const helpText = interpolateComponents( {
			mixedString: __(
				'Your API details can be obtained from your {{link/}}',
				'woocommerce-admin'
			),
			components: {
				link: (
					<Link
						href={ methodConfig.apiDetailsLink }
						target="_blank"
						type="external"
					>
						{ sprintf(
							__( '%(title)s account', 'woocommerce-admin' ),
							{
								title: methodConfig.title,
							}
						) }
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
							{ ( methodConfig.fields || [] ).map( ( field ) => (
								<TextControl
									key={ field.name }
									label={ field.title }
									required
									{ ...getInputProps( field.name ) }
								/>
							) ) }

							<Button
								isPrimary
								isBusy={ isOptionsRequesting }
								onClick={ ( event ) => {
									recordConnectStartEvent( methodConfig.key );
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

	return (
		<Stepper
			isVertical
			isPending={ ! installStep.isComplete || isOptionsRequesting }
			currentStep={ installStep.isComplete ? 'connect' : 'install' }
			steps={ [
				installStep,
				{
					key: 'connect',
					label: sprintf(
						__(
							'Connect your %(title)s account',
							'woocommerce-admin'
						),
						{
							title: methodConfig.title,
						}
					),
					content: renderConnectStep(),
				},
			] }
		/>
	);
}
