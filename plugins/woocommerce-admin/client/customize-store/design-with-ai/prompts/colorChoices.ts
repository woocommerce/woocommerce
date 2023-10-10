/**
 * External dependencies
 */
import { z } from 'zod';
/**
 * Internal dependencies
 */
import { ColorPalette } from '../types';

const colorChoices: ColorPalette[] = [
	{
		name: 'Ancient Bronze',
		primary: '#11163d',
		secondary: '#8C8369',
		foreground: '#11163d',
		background: '#ffffff',
	},
	{
		name: 'Crimson Tide',
		primary: '#A02040',
		secondary: '#234B57',
		foreground: '#871C37',
		background: '#ffffff',
	},
	{
		name: 'Purple Twilight',
		primary: '#301834',
		secondary: '#6a5eb7',
		foreground: '#090909',
		background: '#fefbff',
	},
	{
		name: 'Midnight Citrus',
		primary: '#1B1736',
		secondary: '#7E76A3',
		foreground: '#1B1736',
		background: '#ffffff',
	},
	{
		name: 'Lemon Myrtle',
		primary: '#3E7172',
		secondary: '#FC9B00',
		foreground: '#325C5D',
		background: '#ffffff',
	},
	{
		name: 'Green Thumb',
		primary: '#164A41',
		secondary: '#4B7B4D',
		foreground: '#164A41',
		background: '#ffffff',
	},
	{
		name: 'Golden Haze',
		primary: '#232224',
		secondary: '#EBB54F',
		foreground: '#515151',
		background: '#ffffff',
	},
	{
		name: 'Golden Indigo',
		primary: '#4866C0',
		secondary: '#C09F50',
		foreground: '#405AA7',
		background: '#ffffff',
	},
	{
		name: 'Arctic Dawn',
		primary: '#243156',
		secondary: '#DE5853',
		foreground: '#243156',
		background: '#ffffff',
	},
	{
		name: 'Jungle Sunrise',
		primary: '#1a4435',
		secondary: '#ed774e',
		foreground: '#0a271d',
		background: '#fefbec',
	},
	{
		name: 'Berry Grove',
		primary: '#1F351A',
		secondary: '#DE76DE',
		foreground: '#1f351a',
		background: '#fdfaf1',
	},
	{
		name: 'Fuchsia',
		primary: '#b7127f',
		secondary: '#18020C',
		foreground: '#b7127f',
		background: '#f7edf6',
	},
	{
		name: 'Raspberry Chocolate',
		primary: '#42332e',
		secondary: '#d64d68',
		foreground: '#241d1a',
		background: '#eeeae6',
	},
	{
		name: 'Canary',
		primary: '#0F0F05',
		secondary: '#353535',
		foreground: '#0F0F05',
		background: '#FCFF9B',
	},
	{
		name: 'Gumtree Sunset',
		primary: '#476C77',
		secondary: '#EFB071',
		foreground: '#476C77',
		background: '#edf4f4',
	},
	{
		name: 'Ice',
		primary: '#12123F',
		secondary: '#3473FE',
		foreground: '#12123F',
		background: '#F1F4FA',
	},
	{
		name: 'Cinder',
		primary: '#c14420',
		secondary: '#2F2D2D',
		foreground: '#863119',
		background: '#f1f2f2',
	},
	{
		name: 'Blue Lagoon',
		primary: '#004DE5',
		secondary: '#0496FF',
		foreground: '#0036A3',
		background: '#FEFDF8',
	},
	{
		name: 'Sandalwood Oasis',
		primary: '#F0EBE3',
		secondary: '#DF9785',
		foreground: '#ffffff',
		background: '#2a2a16',
	},
	{
		name: 'Rustic Rosewood',
		primary: '#F4F4F2',
		secondary: '#EE797C',
		foreground: '#ffffff',
		background: '#1A1A1A',
	},
	{
		name: 'Cinnamon Latte',
		primary: '#D9CAB3',
		secondary: '#BC8034',
		foreground: '#FFFFFF',
		background: '#3C3F4D',
	},
	{
		name: 'Lilac Nightshade',
		primary: '#f5d6ff',
		secondary: '#C48DDA',
		foreground: '#ffffff',
		background: '#000000',
	},
	{
		name: 'Lightning',
		primary: '#ebffd2',
		secondary: '#fefefe',
		foreground: '#ebffd2',
		background: '#0e1fb5',
	},
	{
		name: 'Aquamarine Night',
		primary: '#deffef',
		secondary: '#56fbb9',
		foreground: '#ffffff',
		background: '#091C48',
	},
	{
		name: 'Charcoal',
		primary: '#dbdbdb',
		secondary: '#efefef',
		foreground: '#dbdbdb',
		background: '#1e1e1e',
	},
	{
		name: 'Evergreen Twilight',
		primary: '#ffffff',
		secondary: '#8EE978',
		foreground: '#ffffff',
		background: '#181818',
	},
	{
		name: 'Slate',
		primary: '#FFFFFF',
		secondary: '#FFDF6D',
		foreground: '#EFF2F9',
		background: '#13161E',
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
	version: '2023-09-22',
	prompt: ( businessDescription: string, look: string, tone: string ) => {
		return `
            You are a WordPress theme expert designing a WooCommerce site. Analyse the following store description, merchant's chosen look and tone, and determine the most appropriate color scheme, along with 8 best alternatives.
			Do not use any palette names that are not part of the color choices provided below.
			Respond in the form: "{ default: "palette name", bestColors: [ "palette name 1", "palette name 2", "palette name 3", "palette name 4", "palette name 5", "palette name 6", "palette name 7", "palette name 8" ] }"
			
            Chosen look and tone: ${ look } look, ${ tone } tone.
            Business description: ${ businessDescription }

            Colors schemes to choose from: 
            ${ JSON.stringify( colorChoices ) }
        `;
	},
	responseValidation: colorPaletteResponseValidator.parse,
};
