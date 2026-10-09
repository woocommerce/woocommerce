// Script modules can't import @wordpress packages, so this uses the `wp.element` global the screen loads.
const { createElement } = window.wp.element;

export default {
	title: {
		description:
			'Shown to customers at checkout. Added by a script module.',
	},
	// A card's description can't include a link, so this read-only field shows it instead.
	support_description: {
		render: () =>
			createElement(
				'p',
				{ style: { margin: 0, color: '#757575' } },
				'Customers see this address on receipts. ',
				createElement(
					'a',
					{
						href: 'https://woocommerce.com/document/woocommerce-payments/',
					},
					'Learn more'
				)
			),
	},
};
