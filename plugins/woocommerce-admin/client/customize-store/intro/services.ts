// placeholder xstate async service that returns a set of theme cards

export const fetchThemeCards = async () => {
	return [
		{
			name: 'Twenty Twenty One',
			description: 'The default theme for WordPress.',
			image: 'https://i0.wp.com/s2.wp.com/wp-content/themes/pub/twentytwentyone/screenshot.png',
			styleVariations: [],
		},
		{
			name: 'Twenty Twenty',
			description: 'The previous default theme for WordPress.',
			image: 'https://i0.wp.com/s2.wp.com/wp-content/themes/pub/twentytwenty/screenshot.png',
			styleVariations: [],
		},
		{
			name: 'Tsubaki',
			description: 'Tsubaki puts the spotlight on your products and your customers. This theme leverages WooCommerce to provide you with intuitive product navigation and the patterns you need to master digital merchandising.',
			image: 'https://i0.wp.com/s2.wp.com/wp-content/themes/premium/tsubaki/screenshot.png',
			styleVariations: [],
		},
		{
			name: 'Winkel',
			description: 'Winkel is a minimal, product-focused theme featuring Payments block. Its clean, cool look combined with a simple layout makes it perfect for showcasing fashion items – clothes, shoes, and accessories.',
			image: 'https://i0.wp.com/s2.wp.com/wp-content/themes/pub/winkel/screenshot.png',
			styleVariations: [
				{
					title: 'Default',
					primary: '#ffffff',
					secondary: '#676767',
				},
				{
					title: 'Charcoal',
					primary: '#1f2527',
					secondary: '#9fd3e8',					
				},
				{
					title: 'Rainforest',
					primary: '#eef4f7',
					secondary: '#35845d',				
				},
				{
					title: 'Ruby Wine',
					primary: '#ffffff',
					secondary: '#c8133e',
				},
			]
		}
	];
};
