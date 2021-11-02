/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import interpolateComponents from 'interpolate-components';
import { Link, Plugins as PluginInstaller } from '@woocommerce/components';
import { OPTIONS_STORE_NAME, PLUGINS_STORE_NAME } from '@woocommerce/data';
import { recordEvent, queueRecordEvent } from '@woocommerce/tracks';
import { Text } from '@woocommerce/experimental';
import { useDispatch, useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '../../../../lib/notices';
import { ConfigurationStepProps } from '.';
import { SettingsSelector } from '../utils';

export const Plugins: React.FC< ConfigurationStepProps > = ( {
	nextStep,
	onDisable,
	onManual,
	pluginsToActivate,
} ) => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { tosAccepted } = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME ) as SettingsSelector;
		const { getActivePlugins } = select( PLUGINS_STORE_NAME );

		return {
			activePlugins: getActivePlugins(),
			tosAccepted:
				getOption( 'wc_connect_options' )?.tos_accepted ||
				getOption( 'woocommerce_setup_jetpack_opted_in' ) === '1',
		};
	} );

	const agreementText = pluginsToActivate.includes( 'woocommerce-services' )
		? __(
				'By installing Jetpack and WooCommerce Tax you agree to the {{link}}Terms of Service{{/link}}.',
				'woocommerce-admin'
		  )
		: __(
				'By installing Jetpack you agree to the {{link}}Terms of Service{{/link}}.',
				'woocommerce-admin'
		  );

	return (
		<>
			<PluginInstaller
				onComplete={ ( activatedPlugins, response ) => {
					createNoticesFromResponse( response );
					recordEvent( 'tasklist_tax_install_extensions', {
						install_extensions: true,
					} );
					updateOptions( {
						woocommerce_setup_jetpack_opted_in: true,
					} );
					nextStep();
				} }
				onError={ ( errors, response ) =>
					createNoticesFromResponse( response )
				}
				onSkip={ () => {
					queueRecordEvent( 'tasklist_tax_install_extensions', {
						install_extensions: false,
					} );
					onManual();
				} }
				skipText={ __( 'Set up manually', 'woocommerce-admin' ) }
				onAbort={ () => onDisable() }
				abortText={ __(
					"I don't charge sales tax",
					'woocommerce-admin'
				) }
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
								/>
							),
						},
					} ) }
				</Text>
			) }
		</>
	);
};
