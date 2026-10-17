import { createSettingsScreen } from '@woocommerce/settings-screens';

export const { route, stage } = createSettingsScreen( {
	id: 'example/advanced',
	area: 'payments',
	title: 'Advanced settings',
	restPath: '/payment-settings-example/v1/settings',
	parent: { id: 'example', title: 'Example gateway settings' },
} );
