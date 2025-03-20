/**
 * External dependencies
 */
import { z } from 'zod';
/**
 * Internal dependencies
 */
import { ColorPalette, Look } from '../types';

const colorChoices: ColorPalette[] = [
	{
		name: 'Ancient Bronze',
		primary: '#11163d',
		secondary: '#8C8369',
		foreground: '#11163d',
		background: '#ffffff',
		lookAndFeel: [ 'Contemporary', 'Classic' ] as Look[],
	},
	{
		name: 'Arctic Dawn',
		primary: '#243156',
		secondary: '#DE5853',
		foreground: '#243156',
		background: '#ffffff',
		lookAndFeel: [ 'Contemporary' ] as Look[],
	},
	{
		name: 'Bronze Serenity',
		primary: '#1e4b4b',
		secondary: '#9e7047',
		foreground: '#1e4b4b',
		background: '#ffffff',
		lookAndFeel: [ 'Classic' ],
	},
	{
		name: 'Purple Twilight',
		primary: '#301834',
		secondary: '#6a5eb7',
		foreground: '#090909',
		background: '#fefbff',
		lookAndFeel: [ 'Bold' ] as Look[],
	},
	{
		name: 'Candy Store',
		primary: '#293852',
		secondary: '#f1bea7',
		foreground: '#293852',
		background: '#ffffff',
		lookAndFeel: [ 'Classic' ],
	},
	{
		name: 'Midnight Citrus',
		primary: '#1B1736',
		secondary: '#7E76A3',
		foreground: '#1B1736',
		background: '#ffffff',
		lookAndFeel: [ 'Bold', 'Contemporary' ] as Look[],
	},
	{
		name: 'Crimson Tide',
		primary: '#A02040',
		secondary: '#234B57',
		foreground: '#871C37',
		background: '#ffffff',
		lookAndFeel: [ 'Bold' ] as Look[],
	},
	{
		name: 'Raspberry Chocolate',
		primary: '#42332e',
		secondary: '#d64d68',
		foreground: '#241d1a',
		background: '#eeeae6',
		lookAndFeel: [ 'Contemporary', 'Classic' ] as Look[],
	},
	{
		name: 'Gumtree Sunset',
		primary: '#476C77',
		secondary: '#EFB071',
		foreground: '#476C77',
		background: '#edf4f4',
		lookAndFeel: [ 'Classic' ] as Look[],
	},
	{
		name: 'Fuchsia',
		primary: '#b7127f',
		secondary: '#18020C',
		foreground: '#b7127f',
		background: '#f7edf6',
		lookAndFeel: [ 'Bold' ] as Look[],
	},
	{
		name: 'Cinder',
		primary: '#c14420',
		secondary: '#2F2D2D',
		foreground: '#863119',
		background: '#f1f2f2',
		lookAndFeel: [ 'Contemporary', 'Classic' ] as Look[],
	},
	{
		name: 'Canary',
		primary: '#0F0F05',
		secondary: '#353535',
		foreground: '#0F0F05',
		background: '#FCFF9B',
		lookAndFeel: [ 'Bold' ] as Look[],
	},
	{
		name: 'Blue Lagoon',
		primary: '#004DE5',
		secondary: '#0496FF',
		foreground: '#0036A3',
		background: '#FEFDF8',
		lookAndFeel: [ 'Bold', 'Contemporary' ] as Look[],
	},
	{
		name: 'Vibrant Berry',
		primary: '#7C1D6F',
		secondary: '#C62FB2',
		foreground: '#7C1D6F',
		background: '#FFEED6',
		lookAndFeel: [ 'Classic', 'Bold' ],
	},
	{
		name: 'Aquamarine Night',
		primary: '#deffef',
		secondary: '#56fbb9',
		foreground: '#ffffff',
		background: '#091C48',
		lookAndFeel: [ 'Bold' ] as Look[],
	},
	{
		name: 'Evergreen Twilight',
		primary: '#ffffff',
		secondary: '#8EE978',
		foreground: '#ffffff',
		background: '#181818',
		lookAndFeel: [ 'Contemporary', 'Classic' ] as Look[],
	},
	{
		name: 'Cinnamon Latte',
		primary: '#D9CAB3',
		secondary: '#BC8034',
		foreground: '#FFFFFF',
		background: '#3C3F4D',
		lookAndFeel: [ 'Contemporary', 'Classic' ] as Look[],
	},
	{
		name: 'Lightning',
		primary: '#ebffd2',
		secondary: '#fefefe',
		foreground: '#ebffd2',
		background: '#0e1fb5',
		lookAndFeel: [ 'Bold' ] as Look[],
	},
	{
		name: 'Lilac Nightshade',
		primary: '#f5d6ff',
		secondary: '#C48DDA',
		foreground: '#ffffff',
		background: '#000000',
		lookAndFeel: [ 'Contemporary', 'Classic' ] as Look[],
	},
	{
		name: 'Charcoal',
		primary: '#dbdbdb',
		secondary: '#efefef',
		foreground: '#dbdbdb',
		background: '#1e1e1e',
		lookAndFeel: [ 'Contemporary', 'Classic' ] as Look[],
	},
	{
		name: 'Rustic Rosewood',
		primary: '#F4F4F2',
		secondary: '#EE797C',
		foreground: '#ffffff',
		background: '#1A1A1A',
		lookAndFeel: [ 'Contemporary', 'Classic' ] as Look[],
	},
	{
		name: 'Sandalwood Oasis',
		primary: '#F0EBE3',
		secondary: '#DF9785',
		foreground: '#ffffff',
		background: '#2a2a16',
		lookAndFeel: [ 'Contemporary', 'Classic' ] as Look[],
	},
	{
		name: 'Slate',
		primary: '#FFFFFF',
		secondary: '#FFDF6D',
		foreground: '#EFF2F9',
		background: '#13161E',
		lookAndFeel: [ 'Contemporary', 'Classic' ] as Look[],
	},
];

const allowedNames: string[] = colorChoices.map( ( palette ) => palette.name );
const hexColorRegex = /^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/;
const colorPaletteNameValidator = z
	.string()
	.refine( ( name ) => allowedNames.includes( name ), {
		message: 'Color palette not part of allowed list',
	} );

export const colorPaletteValidator = z.object( {
	name: colorPaletteNameValidator,
	primary: z
		.string()
		.regex( hexColorRegex, { message: 'Invalid primary color' } ),
	secondary: z
		.string()
		.regex( hexColorRegex, { message: 'Invalid secondary color' } ),
	foreground: z
		.string()
		.regex( hexColorRegex, { message: 'Invalid foreground color' } ),
	background: z
		.string()
		.regex( hexColorRegex, { message: 'Invalid background color' } ),
	lookAndFeel: z.array( z.enum( [ 'Contemporary', 'Classic', 'Bold' ] ) ),
} );

export const colorPaletteResponseValidator = z
	.object( {
		default: colorPaletteNameValidator,
		bestColors: z.array( colorPaletteNameValidator ).length( 8 ),
	} )
	.refine(
		( response ) => {
			const allColors = [ response.default, ...response.bestColors ];
			const uniqueColors = new Set( allColors );
			return uniqueColors.size === allColors.length;
		},
		{ message: 'Color palette names must be unique' }
	);

export const defaultColorPalette = {
	queryId: 'default_color_palette',

	// make sure version is updated every time the prompt is changed
	version: '2023-10-12',
	prompt: ( businessDescription: string, look: Look | '', tone: string ) => {
		return `
            You are a WordPress theme expert designing a WooCommerce site. Analyse the following store description, merchant's chosen look and tone, and determine the most appropriate color scheme, along with 8 best alternatives.
			Do not use any palette names that are not part of the color choices provided below.
			Respond in the JSON form: "{ "default": "palette name", "bestColors": [ "palette name 1", "palette name 2", "palette name 3", "palette name 4", "palette name 5", "palette name 6", "palette name 7", "palette name 8" ] }"
			
            Chosen look and tone: ${ look } look, ${ tone } tone.
            Business description: ${ businessDescription }

            Colors schemes to choose from: 
            ${ JSON.stringify(
				look
					? colorChoices.filter( ( color ) =>
							color.lookAndFeel.includes( look )
					  )
					: colorChoices
			) }
        `;
	},
	responseValidation: colorPaletteResponseValidator.parse,
};
