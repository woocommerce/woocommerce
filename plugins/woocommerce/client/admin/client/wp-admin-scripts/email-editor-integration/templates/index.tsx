/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { TemplateSenderPanel } from './template_sender_panel';
import './style.scss';

function modifyTemplateSidebar() {
	addFilter(
		'woocommerce_email_editor_template_sections',
		'my-plugin/template-settings',
		( sections ) => [
			...sections,
			{
				id: 'my-custom-section',
				render: () => {
					return <TemplateSenderPanel />;
				},
			},
		]
	);
}

export { modifyTemplateSidebar };
