/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import interpolateComponents from '@automattic/interpolate-components';

/**
 * Internal dependencies
 */
import { PluginInstallError } from '../../../../services/installAndActivatePlugins';
import { joinWithAnd, composeListFormatParts } from '../../Plugins';

export const PluginErrorBanner = ( {
	pluginsInstallationPermissionsFailure,
	pluginsInstallationErrors,
	pluginsSlugToName = {},
	onClick,
}: {
	pluginsInstallationPermissionsFailure?: boolean;
	pluginsInstallationErrors?: PluginInstallError[];
	pluginsSlugToName?: Record< string, string >;
	onClick?: () => void;
} ) => {
	let installationErrorMessage;
	switch ( true ) {
		case pluginsInstallationPermissionsFailure:
		case pluginsInstallationErrors?.some(
			// it really shouldn't get here since permissions are pre-checked. but we'll check for 403 just to be safe.
			( e ) => e.errorDetails?.data?.data?.status === 403 // 403 is the code representing rest_authorization_required_code()
		):
			installationErrorMessage = __(
				'You do not have permissions to install plugins. Please contact your site administrator.',
				'woocommerce'
			);
			break;
		default:
			installationErrorMessage = // Translators: %s is a list of plugins that does not need to be translated
				__(
					'Oops! We encountered a problem while installing %s. {{link}}Please try again{{/link}}.',
					'woocommerce'
				);
			break;
	}

	const failedPluginNames = [
		...new Set(
			( pluginsInstallationErrors || [] ).map(
				// Use the plugin name if available, otherwise use the plugin slug
				( error ) => pluginsSlugToName[ error.plugin ] || error.plugin
			)
		),
	];

	return (
		<p className="plugin-error">
			{ interpolateComponents( {
				mixedString: sprintf(
					installationErrorMessage,
					joinWithAnd( failedPluginNames )
						.map( composeListFormatParts )
						.join( '' )
				),
				components: {
					span: <span />,
					link: <Button variant="link" onClick={ onClick } />,
				},
			} ) }
		</p>
	);
};
