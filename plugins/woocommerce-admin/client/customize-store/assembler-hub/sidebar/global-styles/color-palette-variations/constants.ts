// TODO: Fetch AI-picked color palettes from the backend API
export const COLOR_PALETTES = [
	{
		title: 'New - Neutral',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#000000',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#636363',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#000000',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#ffffff',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#ffffff',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			blocks: {
				'core/button': {},
			},
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--primary)',
						text: 'var(--wp--preset--color--background)',
					},
					':hover': {
						color: {
							background: 'var(--wp--preset--color--secondary)',
							text: 'var(--wp--preset--color--background)',
						},
					},
				},
				link: {
					color: {
						text: 'var(--wp--preset--color--foreground)',
					},
				},
			},
		},
	},
	{
		title: 'Ancient Bronze',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#323856',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#8C8369',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#323856',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#ffffff',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#F7F2EE',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
			},
		},
		wpcom_category: 'Neutral',
	},
	{
		title: 'Arctic Dawn',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#1E226F',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#DD301D',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#0D1263',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#ffffff',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#F0F1F5',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
			},
		},
		wpcom_category: 'Neutral',
	},
	{
		title: 'Bronze Serenity',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#1e4b4b',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#9e7047',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#1e4b4b',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#ffffff',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#e9eded',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
		},
		wpcom_category: 'Neutral',
	},
	{
		title: 'Purple Twilight',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#301834',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#6a5eb7',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#090909',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#fefbff',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#f3eaf5',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
			},
		},
		wpcom_category: 'Neutral',
	},
	{
		title: 'Candy Store',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#293852',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#f1bea7',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#293852',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#ffffff',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#fffddb',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			blocks: {
				'core/button': {
					color: {
						background: 'var(--wp--preset--color--secondary)',
					},
					variations: {
						outline: {
							border: {
								color: 'var(--wp--preset--color--secondary)',
							},
							color: {
								text: 'var(--wp--preset--color--primary)',
							},
						},
					},
				},
			},
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--primary)',
					},
				},
			},
		},
		wpcom_category: 'Neutral',
	},
	{
		title: 'Midnight Citrus',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#222222',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#c0f500',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#222222',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#ffffff',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#f7faed',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			blocks: {
				'core/button': {
					color: {
						background: 'var(--wp--preset--color--secondary)',
					},
					variations: {
						outline: {
							border: {
								color: 'var(--wp--preset--color--secondary)',
							},
							color: {
								text: 'var(--wp--preset--color--primary)',
							},
						},
					},
				},
			},
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					':hover': {
						color: {
							background: 'var(--wp--preset--color--secondary)',
							text: 'var(--wp--preset--color--primary)',
						},
					},
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--primary)',
					},
				},
			},
		},
		wpcom_category: 'Neutral',
	},
	{
		title: 'Crimson Tide',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#101317',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#EC5E3F',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#101317',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#ffffff',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#EEEEEE',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
			},
		},
		wpcom_category: 'Neutral',
	},
	{
		title: 'Raspberry Chocolate',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#42332e',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#d64d68',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#241d1a',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#eeeae6',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#D6CCC2',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
			},
		},
		wpcom_category: 'Bright',
	},
	{
		title: 'Gumtree Sunset',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#8699A1',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#BB6154',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#476C77',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#F4F7F7',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#ffffff',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
		},
		wpcom_category: 'Bright',
	},
	{
		title: 'Fuchsia',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#b7127f',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#18020C',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#b7127f',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#f7edf6',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#ffffff',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
		},
		wpcom_category: 'Bright',
	},
	{
		title: 'Cinder',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#c14420',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#2F2D2D',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#c14420',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#f1f2f2',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#DCDCDC',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
		},
		wpcom_category: 'Bright',
	},
	{
		title: 'Canary',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#0F0F05',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#666666',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#0F0F05',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#FCFF9B',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#E8EB8C',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
			},
		},
		wpcom_category: 'Bright',
	},
	{
		title: 'Blue Lagoon',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#004DE5',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#0496FF',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#0036A3',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#FEFDF8',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#DEF2F7',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
		},
		wpcom_category: 'Bright',
	},
	{
		title: 'Vibrant Berry',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							slug: 'primary',
							color: '#7C1D6F',
							name: 'Primary',
						},
						{
							slug: 'secondary',
							color: '#C62FB2',
							name: 'Secondary',
						},
						{
							slug: 'foreground',
							color: '#7C1D6F',
							name: 'Foreground',
						},
						{
							slug: 'background',
							color: '#FFEED6',
							name: 'Background',
						},
						{
							slug: 'tertiary',
							color: '#FDD8DE',
							name: 'Tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
		},
		wpcom_category: 'Bright',
	},
	{
		title: 'Aquamarine Night',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#deffef',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#56fbb9',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#ffffff',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#091C48',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#10317F',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
		},
		wpcom_category: 'Dark',
	},
	{
		title: 'Evergreen Twilight',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#ffffff',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#8EE978',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#ffffff',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#181818',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#636363',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
		},
		wpcom_category: 'Dark',
	},
	{
		title: 'Cinnamon Latte',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							slug: 'primary',
							color: '#D9CAB3',
							name: 'Primary',
						},
						{
							slug: 'secondary',
							color: '#BC8034',
							name: 'Secondary',
						},
						{
							slug: 'foreground',
							color: '#FFFFFF',
							name: 'Foreground',
						},
						{
							slug: 'background',
							color: '#3C3F4D',
							name: 'Background',
						},
						{
							slug: 'tertiary',
							color: '#2B2D36',
							name: 'Tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
			},
		},
		wpcom_category: 'Dark',
	},
	{
		title: 'Lightning',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#ebffd2',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#fefefe',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#ebffd2',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#0e1fb5',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#0A1680',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
			},
		},
		wpcom_category: 'Dark',
	},
	{
		title: 'Lilac Nightshade',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#f5d6ff',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#C48DDA',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#ffffff',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#000000',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#462749',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
		},
		wpcom_category: 'Dark',
	},
	{
		title: 'Charcoal',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#dbdbdb',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#efefef',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#dbdbdb',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#1e1e1e',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#000000',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
			},
		},
		wpcom_category: 'Dark',
	},
	{
		title: 'Rustic Rosewood',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#F4F4F2',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#EE797C',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#ffffff',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#1A1A1A',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#3B3939',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
			},
		},
		wpcom_category: 'Dark',
	},
	{
		title: 'Sandalwood Oasis',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#F0EBE3',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#DF9785',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#ffffff',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#2a2a16',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#434323',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
		},
		wpcom_category: 'Dark',
	},
	{
		title: 'Slate',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							slug: 'primary',
							color: '#FFFFFF',
							name: 'Primary',
						},
						{
							slug: 'secondary',
							color: '#FFDF6D',
							name: 'Secondary',
						},
						{
							slug: 'foreground',
							color: '#EFF2F9',
							name: 'Foreground',
						},
						{
							slug: 'background',
							color: '#13161E',
							name: 'Background',
						},
						{
							slug: 'tertiary',
							color: '#303036',
							name: 'Tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
			},
		},
		wpcom_category: 'Dark',
	},
	{
		title: 'Blueberry Sorbet',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#2038B6',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#BD4089',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#2038B6',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#FDFBEF',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#F8F2E2',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
			},
		},
	},
	{
		title: 'Green Thumb',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#164A41',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#4B7B4D',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#164A41',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#ffffff',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#CEEAC4',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
				link: {
					color: {
						text: 'var(--wp--preset--color--secondary)',
					},
					':hover': {
						color: {
							text: 'var(--wp--preset--color--foreground)',
						},
					},
				},
			},
		},
	},
	{
		title: 'Golden Haze',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#232224',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#EBB54F',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#515151',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#ffffff',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#FFF0AE',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--foreground)',
					},
				},
				link: {
					color: {
						text: 'var(--wp--preset--color--secondary)',
					},
					':hover': {
						color: {
							text: 'var(--wp--preset--color--foreground)',
						},
					},
				},
			},
		},
	},
	{
		title: 'Golden Indigo',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							color: '#4866C0',
							name: 'Primary',
							slug: 'primary',
						},
						{
							color: '#C09F50',
							name: 'Secondary',
							slug: 'secondary',
						},
						{
							color: '#405AA7',
							name: 'Foreground',
							slug: 'foreground',
						},
						{
							color: '#ffffff',
							name: 'Background',
							slug: 'background',
						},
						{
							color: '#FBF5EE',
							name: 'Tertiary',
							slug: 'tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
				link: {
					color: {
						text: 'var(--wp--preset--color--secondary)',
					},
					':hover': {
						color: {
							text: 'var(--wp--preset--color--foreground)',
						},
					},
				},
			},
		},
	},
	{
		title: 'Ice',
		version: 2,
		settings: {
			color: {
				palette: {
					theme: [
						{
							slug: 'primary',
							color: '#3473FE',
							name: 'Primary',
						},
						{
							slug: 'secondary',
							color: '#12123F',
							name: 'Secondary',
						},
						{
							slug: 'foreground',
							color: '#12123F',
							name: 'Foreground',
						},
						{
							slug: 'background',
							color: '#F1F4FA',
							name: 'Background',
						},
						{
							slug: 'tertiary',
							color: '#DBE6EE',
							name: 'Tertiary',
						},
					],
				},
			},
		},
		styles: {
			color: {
				background: 'var(--wp--preset--color--background)',
				text: 'var(--wp--preset--color--foreground)',
			},
			elements: {
				button: {
					color: {
						background: 'var(--wp--preset--color--secondary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
				link: {
					color: {
						text: 'var(--wp--preset--color--foreground)',
					},
					':hover': {
						color: {
							text: 'var(--wp--preset--color--primary)',
						},
					},
				},
			},
		},
	},
].map( ( color ) => {
	// Add base styles settings for elements to ensure that the colors are applied correctly since default TT3 theme does not have right styles.
	// These styles are referenced in the theme.json file of the creatio-2 theme.
	// https://github.com/Automattic/themes/blob/trunk/creatio-2/theme.json
	return {
		...color,
		styles: {
			...color.styles,
			blocks: {
				'core/button': {
					color: {
						background: 'var(--wp--preset--color--secondary)',
					},
					variations: {
						outline: {
							border: {
								color: 'var(--wp--preset--color--secondary)',
							},
							color: {
								text: 'var(--wp--preset--color--secondary)',
							},
						},
					},
				},
				'core/heading': {
					color: {
						text: 'var(--wp--preset--color--foreground)',
					},
					elements: {
						link: {
							color: {
								text: 'var(--wp--preset--color--foreground)',
							},
						},
					},
				},
				'core/post-date': {
					color: {
						text: 'var(--wp--preset--color--foreground)',
					},
				},
				'core/post-title': {
					color: {
						text: 'var(--wp--preset--color--foreground)',
					},
					elements: {
						link: {
							':hover': {
								color: {
									text: 'var(--wp--preset--color--primary)',
								},
							},
							color: {
								text: 'var(--wp--preset--color--foreground)',
							},
						},
					},
				},
				'core/pullquote': {
					border: {
						color: 'var(--wp--preset--color--foreground)',
						style: 'solid',
						width: '1px 0',
					},
				},
				'core/quote': {
					border: {
						color: 'var(--wp--preset--color--foreground)',
						style: 'solid',
						width: '0 0 0 5px',
					},
				},
				'core/separator': {
					color: {
						text: 'var(--wp--preset--color--foreground)',
					},
				},
				'core/site-title': {
					elements: {
						link: {
							':hover': {
								color: {
									text: 'var(--wp--preset--color--foreground)',
								},
							},
							color: {
								text: 'var(--wp--preset--color--foreground)',
							},
						},
					},
				},
				...color.styles.blocks,
			},
			elements: {
				heading: {
					color: {
						text: 'var(--wp--preset--color--foreground)',
					},
				},
				button: {
					':active': {
						color: {
							background: 'var(--wp--preset--color--foreground)',
							text: 'var(--wp--preset--color--background)',
						},
					},
					':focus': {
						color: {
							background: 'var(--wp--preset--color--foreground)',
							text: 'var(--wp--preset--color--background)',
						},
						outline: {
							color: 'var(--wp--preset--color--primary)',
							offset: '2px',
							style: 'dotted',
							width: '1px',
						},
					},
					':hover': {
						color: {
							background: 'var(--wp--preset--color--secondary)',
							text: 'var(--wp--preset--color--background)',
						},
					},
					':visited': {
						color: {
							text: color.styles.elements?.button
								? color.styles.elements.button.color
								: 'var(--wp--preset--color--background)',
						},
					},
					color: {
						background: 'var(--wp--preset--color--primary)',
						text: 'var(--wp--preset--color--background)',
					},
				},
				link: {
					':hover': {
						color: {
							text: 'var(--wp--preset--color--primary)',
						},
						typography: {
							textDecoration: 'none',
						},
					},
					color: {
						text: 'var(--wp--preset--color--foreground)',
					},
				},
				...color.styles.elements,
			},
		},
	};
} );

export const DEFAULT_COLOR_PALETTE_TITLES = [
	'New - Neutral',
	'Blueberry Sorbet',
	'Ancient Bronze',
	'Crimson Tide',
	'Purple Twilight',
	'Green Thumb',
	'Golden Haze',
	'Golden Indigo',
	'Arctic Dawn',
	'Raspberry Chocolate',
	'Canary',
	'Ice',
	'Rustic Rosewood',
	'Cinnamon Latte',
	'Lightning',
	'Aquamarine Night',
	'Charcoal',
	'Slate',
];

export const DEFAULT_COLOR_PALETTES = DEFAULT_COLOR_PALETTE_TITLES.map(
	( title ) => COLOR_PALETTES.find( ( palette ) => palette.title === title )
);
