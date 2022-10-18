/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import { Link, Plugins as PluginInstaller } from '@woocommerce/components';
import { OPTIONS_STORE_NAME, InstallPluginsResponse } from '@woocommerce/data';
import { recordEvent, queueRecordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '~/lib/notices';
import { SetupStepProps } from './setup';

const isWcConnectOptions = (
	wcConnectOptions: unknown
): wcConnectOptions is {
	[ key: string ]: unknown;
} => typeof wcConnectOptions === 'object' && wcConnectOptions !== null;

export const Plugins: React.FC< SetupStepProps > = ( {
	nextStep,
	onDisable,
	onManual,
	pluginsToActivate,
} ) => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { isResolving, tosAccepted } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );
		const wcConnectOptions = getOption( 'wc_connect_options' );

		return {
			isResolving:
				! hasFinishedResolution( 'getOption', [
					'woocommerce_setup_jetpack_opted_in',
				] ) ||
				! hasFinishedResolution( 'getOption', [
					'wc_connect_options',
				] ),
			tosAccepted:
				( isWcConnectOptions( wcConnectOptions ) &&
					wcConnectOptions?.tos_accepted ) ||
				getOption( 'woocommerce_setup_jetpack_opted_in' ) === '1',
		};
	} );

	useEffect( () => {
		if ( ! tosAccepted || pluginsToActivate.length ) {
			return;
		}

		nextStep();
	}, [ isResolving ] );

	const agreementText = pluginsToActivate.includes( 'woocommerce-services' )
		? __(
				'By installing Jetpack and WooCommerce Tax you agree to the {{link}}Terms of Service{{/link}}.',
				'woocommerce'
		  )
		: __(
				'By installing Jetpack you agree to the {{link}}Terms of Service{{/link}}.',
				'woocommerce'
		  );

	if ( isResolving ) {
		return null;
	}

	return (
		<>
			<PluginInstaller
				onComplete={ (
					activatedPlugins: string[],
					response: InstallPluginsResponse
				) => {
					createNoticesFromResponse( response );
					recordEvent( 'tasklist_tax_install_extensions', {
						install_extensions: true,
					} );
					updateOptions( {
						woocommerce_setup_jetpack_opted_in: true,
					} );
					nextStep();
				} }
				onError={ ( errors: unknown, response: unknown ) =>
					createNoticesFromResponse( response )
				}
				onSkip={ () => {
					queueRecordEvent( 'tasklist_tax_install_extensions', {
						install_extensions: false,
					} );
					onManual();
				} }
				skipText={ __( 'Set up manually', 'woocommerce' ) }
				onAbort={ () => onDisable() }
				abortText={ __( "I don't charge sales tax", 'woocommerce' ) }
			/>
			{ ! tosAccepted && (
				<Text
					variant="caption"
					className="woocommerce-task__caption"
					size="12"
					lineHeight="16px"
				>
					{ interpolateComponents( {
						mixedString: agreementText,
						components: {
							link: (
								<Link
									href={ 'https://wordpress.com/tos/' }
									target="_blank"
									type="external"
								>
									<></>
								</Link>
							),
						},
					} ) }
				</Text>
			) }
		</>
	);
};
