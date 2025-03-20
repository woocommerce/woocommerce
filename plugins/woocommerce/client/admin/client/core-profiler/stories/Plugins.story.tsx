/**
 * Internal dependencies
 */
import { Plugins } from '../pages/Plugins/Plugins';
import { NoPermissionsError } from '../pages/Plugins/NoPermissions';
import { PluginsTermsOfService } from '../pages/Plugins/components/plugin-terms-of-service/PluginsTermsOfService';
import { PluginErrorBanner } from '../pages/Plugins/components/plugin-error-banner/PluginErrorBanner';

import '../style.scss';
import { WithSetupWizardLayout } from './WithSetupWizardLayout';

const plugins = [
	{
		name: 'Jetpack',
		description:
			'Get auto real-time backups, malware scans, and spam protection.',
		is_visible: true,
		is_built_by_wc: false,
		min_wp_version: '6.0',
		key: 'jetpack',
		label: 'Enhance security with Jetpack',
		image_url:
			'https://woocommerce.com/wp-content/plugins/wccom-plugins/obw-free-extensions/images/core-profiler/logo-jetpack.svg',
		learn_more_link: 'https://woocommerce.com/products/jetpack',
		install_priority: 8,
		is_installed: true,
		is_activated: true,
		manage_url: '',
	},
	{
		name: 'Pinterest for WooCommerce',
		description: 'Get your products in front of a highly engaged audience.',
		image_url:
			'https://woocommerce.com/wp-content/plugins/wccom-plugins/obw-free-extensions/images/core-profiler/logo-pinterest.svg',
		manage_url: 'admin.php?page=wc-admin&path=%2Fpinterest%2Flanding',
		is_built_by_wc: true,
		min_php_version: '7.3',
		key: 'pinterest-for-woocommerce',
		label: 'Showcase your products with Pinterest',
		learn_more_link:
			'https://woocommerce.com/products/pinterest-for-woocommerce',
		install_priority: 2,
		is_visible: true,
		is_installed: false,
		is_activated: false,
	},
];

export const Basic = () => (
	<Plugins
		sendEvent={ () => {} }
		navigationProgress={ 80 }
		context={ {
			pluginsAvailable: plugins,
			pluginsSelected: [],
			pluginsInstallationErrors: [],
		} }
	/>
);

export const InstallationError = () => (
	<Plugins
		sendEvent={ () => {} }
		navigationProgress={ 80 }
		context={ {
			pluginsAvailable: plugins,
			pluginsSelected: [],
			pluginsInstallationErrors: [
				{
					plugin: 'Jetpack',
					errorDetails: {
						data: {
							code: 'plugin_install_failed',
							data: {
								status: 403,
							},
						},
					},
					error: 'Installation failed',
				},
			],
		} }
	/>
);

export const TermsOfService = () => (
	<PluginsTermsOfService selectedPlugins={ plugins } />
);

export const InstallationErrorBanner = () => (
	<div className="woocommerce-profiler-plugins">
		<PluginErrorBanner
			pluginsInstallationPermissionsFailure={ false }
			pluginsInstallationErrors={ [
				{
					plugin: 'Jetpack',
					errorDetails: {
						data: {
							code: 'plugin_install_failed',
							data: {
								status: 403,
							},
						},
					},
					error: 'Installation failed',
				},
			] }
			onClick={ () => {} }
		/>
	</div>
);

export const InstallationNoPermissionError = () => (
	<NoPermissionsError
		sendEvent={ () => {} }
		navigationProgress={ 80 }
		context={ {
			pluginsAvailable: plugins,
		} }
	/>
);

export default {
	title: 'WooCommerce Admin/Core Profiler/Plugins',
	component: Plugins,
	decorators: [ WithSetupWizardLayout ],
};
