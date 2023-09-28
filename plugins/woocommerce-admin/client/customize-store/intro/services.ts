// placeholder xstate async service that returns a set of theme cards

export const fetchThemeCards = async () => {
	return [
		{
			slug: 'twentytwentyone',
			name: 'Twenty Twenty One',
			description: 'The default theme for WordPress.',
			isActive: true,
			image: 'https://i0.wp.com/s2.wp.com/wp-content/themes/pub/twentytwentyone/screenshot.png',
			colorPalettes: [],
		},
		{
			slug: 'twentytwenty',
			name: 'Twenty Twenty',
			description: 'The previous default theme for WordPress.',
			image: 'https://i0.wp.com/s2.wp.com/wp-content/themes/pub/twentytwenty/screenshot.png',
			colorPalettes: [],
		},
		{
			slug: 'tsubaki',
			name: 'Tsubaki',
			description:
				'Tsubaki puts the spotlight on your products and your customers. This theme leverages WooCommerce to provide you with intuitive product navigation and the patterns you need to master digital merchandising.',
			image: 'https://i0.wp.com/s2.wp.com/wp-content/themes/premium/tsubaki/screenshot.png',
			colorPalettes: [],
		},
		{
			slug: 'winkel',
			name: 'Winkel',
			description:
				'Winkel is a minimal, product-focused theme featuring Payments block. Its clean, cool look combined with a simple layout makes it perfect for showcasing fashion items – clothes, shoes, and accessories.',
			image: 'https://i0.wp.com/s2.wp.com/wp-content/themes/pub/winkel/screenshot.png',
			colorPalettes: [
				{
					title: 'Default',
					primary: '#ffffff',
					primary_border: '#eaeaea',
					secondary: '#676767',
					secondary_border: '#eaeaea',
				},
				{
					title: 'Charcoal',
					primary: '#1f2527',
					primary_border: '#eaeaea',
					secondary: '#9fd3e8',
					secondary_border: '#eaeaea',
				},
				{
					title: 'Rainforest',
					primary: '#eef4f7',
					primary_border: '#eaeaea',
					secondary: '#35845d',
					secondary_border: '#eaeaea',
				},
				{
					title: 'Ruby Wine',
					primary: '#ffffff',
					primary_border: '#eaeaea',
					secondary: '#c8133e',
					secondary_border: '#eaeaea',
				},
			],
		},
	];
};
