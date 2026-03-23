/* eslint-disable no-console */
( function addAbbreviatedNotificationFill() {
	if (
		! window.wp?.element ||
		! window.wp?.components?.Fill ||
		! window.wp?.plugins?.registerPlugin
	) {
		console.error(
			'[wc-admin] Missing required WordPress globals: wp.element, wp.components.Fill, or wp.plugins.registerPlugin.'
		);
		return;
	}

	const { createElement } = window.wp.element;
	const { Fill } = window.wp.components;
	const { registerPlugin } = window.wp.plugins;
	const pluginName = 'wc-admin-debug-abbreviated-notification-fill';

	const DebugAbbreviatedNotificationFill = () =>
		createElement(
			Fill,
			{ name: 'AbbreviatedNotification' },
			createElement(
				'div',
				{
					style: {
						padding: '12px',
						marginBottom: '12px',
						border: '1px solid #dcdcde',
						borderRadius: '4px',
						background: '#fff',
					},
				},
				createElement(
					'strong',
					null,
					'Debug abbreviated notification fill'
				),
				createElement(
					'p',
					{ style: { margin: '8px 0 0' } },
					'Injected via window.wp.element for SlotFill testing.'
				)
			)
		);

	registerPlugin( pluginName, {
		render: DebugAbbreviatedNotificationFill,
	} );

	console.info(
		'[wc-admin] Registered debug fill for "AbbreviatedNotification".'
	);
} )();
