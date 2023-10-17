/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Plugins as PluginInstaller } from '@woocommerce/components';
import { OPTIONS_STORE_NAME, InstallPluginsResponse } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '~/lib/notices';
import { TermsOfService } from '~/task-lists/components/terms-of-service';

const isWcConnectOptions = (
	wcConnectOptions: unknown
): wcConnectOptions is {
	[ key: string ]: unknown;
} => typeof wcConnectOptions === 'object' && wcConnectOptions !== null;

type Props = {
	nextStep: () => void;
	pluginsToActivate: string[];
};

export const Plugins: React.FC< Props > = ( {
	nextStep,
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
	}, [ nextStep, pluginsToActivate, tosAccepted ] );

	if ( isResolving ) {
		return null;
	}

	return (
		<>
			{ ! tosAccepted && (
				<TermsOfService
					buttonText={ __( 'Install & enable', 'woocommerce' ) }
				/>
			) }
			<PluginInstaller
				onComplete={ (
					activatedPlugins: string[],
					response: InstallPluginsResponse
				) => {
					createNoticesFromResponse( response );
					recordEvent(
						'tasklist_shipping_recommendation_install_extensions',
						{
							install_extensions: true,
						}
					);
					updateOptions( {
						woocommerce_setup_jetpack_opted_in: true,
					} );
					nextStep();
				} }
				onError={ ( errors: unknown, response: unknown ) =>
					createNoticesFromResponse( response )
				}
				pluginSlugs={ pluginsToActivate }
			/>
		</>
	);
};
