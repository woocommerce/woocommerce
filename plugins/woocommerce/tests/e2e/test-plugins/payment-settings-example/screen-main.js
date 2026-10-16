import { createSettingsScreen } from '@woocommerce/settings-screens';

export const { route, stage } = createSettingsScreen( {
	id: 'example',
	area: 'payments',
	title: 'Example gateway settings',
	restPath: '/payment-settings-example/v1/settings',
} );
