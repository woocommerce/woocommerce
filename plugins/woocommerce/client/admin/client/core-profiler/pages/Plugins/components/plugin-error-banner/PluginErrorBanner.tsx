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
	pluginsInstallationErrors,
	onClick,
}: {
	pluginsInstallationErrors: PluginInstallError[];
	onClick: () => void;
} ) => {
	let installationErrorMessage;
	switch ( true ) {
		case pluginsInstallationErrors.some(
			( e ) => e.errorDetails?.data.code === 'rest_no_permissions'
		):
			installationErrorMessage = // Translators: %s is a list of plugins that does not need to be translated
				__(
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
	return (
		<p className="plugin-error">
			{ interpolateComponents( {
				mixedString: sprintf(
					installationErrorMessage,
					joinWithAnd(
						pluginsInstallationErrors.map(
							( error ) => error.plugin
						)
					)
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
