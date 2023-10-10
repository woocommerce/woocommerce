export const FONT_PREVIEW_LARGE_WIDTH = 136;
export const FONT_PREVIEW_LARGE_HEIGHT = 106;
export const FONT_PREVIEW_WIDTH = 120;
export const FONT_PREVIEW_HEIGHT = 74;
export const SYSTEM_FONT_SLUG = 'system-font';

// Generated from /wpcom/v2/sites/{site_id}/global-styles-variation/font-pairings
// TODO: Consider creating an API endpoint for this data
export const FONT_PAIRINGS = [
	{
		title: 'Inter + Inter',
		version: 2,
		settings: {
			typography: {
				fontFamilies: {
					theme: [
						{
							fontFamily: 'Inter',
							slug: 'inter',
						},
					],
				},
			},
		},
		styles: {
			elements: {
				button: {
					typography: {
						fontFamily: 'var(--wp--preset--font-family--inter)',
						fontWeight: '400',
						lineHeight: '1',
					},
				},
				heading: {
					typography: {
						fontFamily: 'var(--wp--preset--font-family--inter)',
						fontStyle: 'normal',
						fontWeight: '600',
						lineHeight: '1.2',
					},
				},
			},
			blocks: {
				'core/site-title': {
					typography: {
						fontFamily: 'var(--wp--preset--font-family--inter)',
						fontSize: 'var(--wp--preset--font-size--medium)',
						fontStyle: 'normal',
					},
				},
				'core/post-navigation-link': {
					typography: {
						fontFamily: 'var(--wp--preset--font-family--inter)',
					},
				},
			},
			typography: {
				fontFamily: 'var(--wp--preset--font-family--inter)',
				fontSize: 'var(--wp--preset--font-size--medium)',
				fontStyle: 'normal',
				fontWeight: '400',
				lineHeight: '1.6',
			},
		},
	},
	{
		title: 'Bodoni Moda + Overpass',
		version: 2,
		settings: {
			typography: {
				fontFamilies: {
					theme: [
						{
							fontFamily: 'Bodoni Moda',
							slug: 'bodoni-moda',
						},
						{
							fontFamily: 'Overpass',
							slug: 'overpass',
						},
					],
				},
			},
		},
		styles: {
			elements: {
				button: {
					typography: {
						fontFamily: 'var(--wp--preset--font-family--overpass)',
						fontWeight: '400',
						lineHeight: '1',
					},
				},
				heading: {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--bodoni-moda)',
						fontStyle: 'normal',
						fontWeight: '400',
					},
				},
			},
			blocks: {
				'core/site-title': {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--bodoni-moda)',
					},
				},
				'core/post-navigation-link': {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--bodoni-moda)',
					},
				},
			},
			typography: {
				fontFamily: 'var(--wp--preset--font-family--overpass)',
				fontSize: 'var(--wp--preset--font-size--medium)',
				fontStyle: 'normal',
				fontWeight: '300',
				lineHeight: '1.6',
			},
		},
	},
	{
		title: 'Commissioner + Crimson Pro',
		version: 2,
		settings: {
			typography: {
				fontFamilies: {
					theme: [
						{
							fontFamily: 'Commissioner',
							slug: 'commissioner',
						},
						{
							fontFamily: 'Crimson Pro',
							slug: 'crimson-pro',
						},
					],
				},
			},
		},
		styles: {
			elements: {
				button: {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--commissioner)',
						fontWeight: '400',
						lineHeight: '1',
					},
				},
				heading: {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--commissioner)',
						fontStyle: 'normal',
						fontWeight: '300',
					},
				},
			},
			blocks: {
				'core/site-title': {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--commissioner)',
						fontWeight: '300',
					},
				},
				'core/post-navigation-link': {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--commissioner)',
					},
				},
			},
			typography: {
				fontFamily: 'var(--wp--preset--font-family--crimson-pro)',
				fontSize: 'var(--wp--preset--font-size--medium)',
				fontStyle: 'normal',
				fontWeight: '400',
				lineHeight: '1.6',
			},
		},
	},
	{
		title: 'Libre Baskerville + DM Sans',
		version: 2,
		settings: {
			typography: {
				fontFamilies: {
					theme: [
						{
							fontFamily: 'Libre Baskerville',
							slug: 'libre-baskerville',
						},
						{
							fontFamily: 'DM Sans',
							slug: 'dm-sans',
						},
					],
				},
			},
		},
		styles: {
			elements: {
				button: {
					typography: {
						fontFamily: 'var(--wp--preset--font-family--dm-sans)',
						fontWeight: '400',
						lineHeight: '1',
					},
				},
				heading: {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--libre-baskerville)',
						fontStyle: 'normal',
						fontWeight: '700',
					},
				},
			},
			blocks: {
				'core/site-title': {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--libre-baskerville)',
					},
				},
				'core/post-navigation-link': {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--libre-baskerville)',
					},
				},
			},
			typography: {
				fontFamily: 'var(--wp--preset--font-family--dm-sans)',
				fontSize: 'var(--wp--preset--font-size--small)',
				fontStyle: 'normal',
				fontWeight: '400',
				lineHeight: '1.6',
			},
		},
	},
	{
		title: 'Libre Franklin + EB Garamond',
		version: 2,
		settings: {
			typography: {
				fontFamilies: {
					theme: [
						{
							fontFamily: 'Libre Franklin',
							slug: 'libre-franklin',
						},
						{
							fontFamily: 'EB Garamond',
							slug: 'eb-garamond',
						},
					],
				},
			},
		},
		styles: {
			elements: {
				button: {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--libre-franklin)',
						fontSize: 'var(--wp--preset--font-size--small)',
						fontWeight: '400',
						lineHeight: '1',
					},
				},
				heading: {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--libre-franklin)',
						fontStyle: 'normal',
						fontWeight: '700',
					},
				},
			},
			blocks: {
				'core/site-title': {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--libre-franklin)',
						fontWeight: '500',
					},
				},
				'core/post-navigation-link': {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--libre-franklin)',
					},
				},
			},
			typography: {
				fontFamily: 'var(--wp--preset--font-family--eb-garamond)',
				fontSize: 'var(--wp--preset--font-size--medium)',
				fontStyle: 'normal',
				fontWeight: '400',
				lineHeight: '1.6',
			},
		},
	},
	{
		title: 'Montserrat + Arvo',
		version: 2,
		settings: {
			typography: {
				fontFamilies: {
					theme: [
						{
							fontFamily: 'Montserrat',
							slug: 'montserrat',
						},
						{
							fontFamily: 'Arvo',
							slug: 'arvo',
						},
					],
				},
			},
		},
		styles: {
			elements: {
				button: {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--montserrat)',
						fontStyle: 'normal',
						fontWeight: '500',
					},
				},
				heading: {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--montserrat)',
						fontStyle: 'normal',
						fontWeight: '700',
						lineHeight: '1.4',
					},
				},
			},
			blocks: {
				'core/site-title': {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--montserrat)',
						fontWeight: '700',
					},
				},
				'core/post-navigation-link': {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--montserrat)',
					},
				},
			},
			typography: {
				fontFamily: 'var(--wp--preset--font-family--arvo)',
				fontSize: 'var(--wp--preset--font-size--small)',
				fontStyle: 'normal',
				fontWeight: '400',
				lineHeight: '1.6',
			},
		},
	},
	{
		title: 'Playfair Display + Fira Sans',
		version: 2,
		settings: {
			typography: {
				fontFamilies: {
					theme: [
						{
							fontFamily: 'Playfair Display',
							slug: 'playfair-display',
						},
						{
							fontFamily: 'Fira Sans',
							slug: 'fira-sans',
						},
					],
				},
			},
		},
		styles: {
			elements: {
				button: {
					typography: {
						fontFamily: 'var(--wp--preset--font-family--fira-sans)',
						fontWeight: '400',
						lineHeight: '1',
					},
				},
				heading: {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--playfair-display)',
						fontStyle: 'italic',
						fontWeight: '400',
					},
				},
			},
			blocks: {
				'core/site-title': {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--playfair-display)',
						fontStyle: 'italic',
						fontWeight: '400',
					},
				},
				'core/post-navigation-link': {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--playfair-display)',
						fontStyle: 'italic',
						fontWeight: '400',
					},
				},
			},
			typography: {
				fontFamily: 'var(--wp--preset--font-family--fira-sans)',
				fontSize: 'var(--wp--preset--font-size--medium)',
				fontStyle: 'normal',
				fontWeight: '400',
				lineHeight: '1.6',
			},
		},
	},
	{
		title: 'Rubik + Inter',
		version: 2,
		settings: {
			typography: {
				fontFamilies: {
					theme: [
						{
							fontFamily: 'Rubik',
							slug: 'rubik',
						},
						{
							fontFamily: 'Inter',
							slug: 'inter',
						},
					],
				},
			},
		},
		styles: {
			elements: {
				button: {
					typography: {
						fontFamily: 'var(--wp--preset--font-family--inter)',
						fontWeight: '400',
						lineHeight: '1',
					},
				},
				heading: {
					typography: {
						fontFamily: 'var(--wp--preset--font-family--rubik)',
						fontStyle: 'normal',
						fontWeight: '800',
					},
				},
			},
			blocks: {
				'core/site-title': {
					typography: {
						fontFamily: 'var(--wp--preset--font-family--rubik)',
						fontWeight: '800',
					},
				},
				'core/post-navigation-link': {
					typography: {
						fontFamily: 'var(--wp--preset--font-family--rubik)',
					},
				},
			},
			typography: {
				fontFamily: 'var(--wp--preset--font-family--inter)',
				fontSize: 'var(--wp--preset--font-size--medium)',
				fontStyle: 'normal',
				fontWeight: '400',
				lineHeight: '1.6',
			},
		},
	},
	{
		title: 'Space Mono + Roboto',
		version: 2,
		settings: {
			typography: {
				fontFamilies: {
					theme: [
						{
							fontFamily: 'Space Mono',
							slug: 'space-mono',
						},
						{
							fontFamily: 'Roboto',
							slug: 'roboto',
						},
					],
				},
			},
		},
		styles: {
			elements: {
				button: {
					typography: {
						fontFamily: 'var(--wp--preset--font-family--roboto)',
						fontWeight: '400',
						lineHeight: '1',
					},
				},
				heading: {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--space-mono)',
						fontStyle: 'normal',
						fontWeight: '400',
						lineHeight: '1.15',
					},
				},
			},
			blocks: {
				'core/site-title': {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--space-mono)',
						fontStyle: 'normal',
						fontWeight: '400',
					},
				},
				'core/post-navigation-link': {
					typography: {
						fontFamily:
							'var(--wp--preset--font-family--space-mono)',
					},
				},
			},
			typography: {
				fontFamily: 'var(--wp--preset--font-family--roboto)',
				fontSize: 'var(--wp--preset--font-size--small)',
				fontStyle: 'normal',
				fontWeight: '400',
				lineHeight: '1.6',
			},
		},
	},
];
