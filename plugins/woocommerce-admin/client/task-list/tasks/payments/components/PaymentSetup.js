/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Card, CardBody } from '@wordpress/components';
import { cloneElement, useMemo } from '@wordpress/element';
import { Plugins } from '@woocommerce/components';
import { PLUGINS_STORE_NAME, pluginNames } from '@woocommerce/data';
import { recordEvent } from '@woocommerce/tracks';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { createNoticesFromResponse } from '~/lib/notices';

export const PaymentSetup = ( { method, markConfigured, query } ) => {
	const { activePlugins } = useSelect( ( select ) => {
		const { getActivePlugins } = select( PLUGINS_STORE_NAME );

		return {
			activePlugins: getActivePlugins(),
		};
	} );

	const installStep = useMemo( () => {
		if ( ! method.plugins || ! method.plugins.length ) {
			return;
		}

		const pluginsToInstall = method.plugins.filter(
			( m ) => ! activePlugins.includes( m )
		);
		const pluginNamesString = method.plugins
			.map( ( pluginSlug ) => pluginNames[ pluginSlug ] )
			.join( ' ' + __( 'and', 'woocommerce-admin' ) + ' ' );

		return {
			key: 'install',
			label: sprintf(
				/* translators: %s = one or more plugin names joined by "and" */
				__( 'Install %s', 'woocommerce-admin' ),
				pluginNamesString
			),
			content: (
				<Plugins
					onComplete={ ( plugins, response ) => {
						createNoticesFromResponse( response );
						recordEvent( 'tasklist_payment_install_method', {
							plugins: method.plugins,
						} );
					} }
					onError={ ( errors, response ) =>
						createNoticesFromResponse( response )
					}
					autoInstall
					pluginSlugs={ method.plugins }
				/>
			),
			isComplete: ! pluginsToInstall.length,
		};
	}, [ activePlugins, method.plugins ] );

	if ( ! method.container ) {
		return null;
	}

	return (
		<Card className="woocommerce-task-payment-method woocommerce-task-card">
			<CardBody>
				{ cloneElement( method.container, {
					methodConfig: method,
					query,
					installStep,
					markConfigured,
					hasCbdIndustry: method.hasCbdIndustry,
				} ) }
			</CardBody>
		</Card>
	);
};
