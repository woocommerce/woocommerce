/**
 * Internal dependencies
 */
import { getHasColorClasses, getStyleColorVars } from '../';

describe( 'getHasColorClasses', () => {
	it( 'returns classes with "normal" colors, prioritizing them over "custom" colors', () => {
		const attributes = {
			className: 'wc-block-product-filter-checkbox-list',
			count: 5,

			warningText: 'dark',
			customWarningText: '#000011',

			warningBackground: 'light',
			customWarningBackground: '#aaffff',
		};

		const colorNames = [ 'warningText', 'warningBackground' ];

		const result = getHasColorClasses( attributes, colorNames );

		expect( result ).toStrictEqual( {
			'has-warning-text-color': 'dark',
			'has-warning-background-color': 'light',
		} );
	} );

	it( 'returns classes with "custom" colors when "normal" colors are not defined', () => {
		const attributes = {
			className: 'wc-block-product-filter-checkbox-list',
			count: 5,

			warningText: '',
			customWarningText: '#000011',
			warningBackground: undefined,
			customWarningBackground: '#aaffff',
		};

		const colorNames = [ 'warningText', 'warningBackground' ];

		const result = getHasColorClasses( attributes, colorNames );

		expect( result ).toStrictEqual( {
			'has-warning-text-color': '#000011',
			'has-warning-background-color': '#aaffff',
		} );
	} );
} );

describe( 'getStyleColorVars', () => {
	it( 'generates CSS variables with normal and custom color values', () => {
		const attributes = {
			className: 'wc-custom-block',
			count: 5,

			warningTextColor: 'dark',
			customWarningTextColor: '#000011',

			warningBackgroundColor: 'light',
			customWarningBackgroundColor: '#aaffff',
		};

		const colors = [ 'warningTextColor', 'warningBackgroundColor' ];

		const result = getStyleColorVars( 'retro', attributes, colors );

		expect( result ).toStrictEqual( {
			'--retro-warning-text-color': 'var(--wp--preset--color--dark)',
			'--retro-warning-background-color':
				'var(--wp--preset--color--light)',
		} );
	} );

	it( 'generates CSS variables using custom color values when normal values are undefined', () => {
		const attributes = {
			className: 'wc-custom-block',
			count: 5,

			warningTextColor: undefined,
			customWarningTextColor: '#000011',
			warningBackgroundColor: undefined,
			customWarningBackgroundColor: '#aaffff',
		};

		const colors = [ 'warningTextColor', 'warningBackgroundColor' ];

		const result = getStyleColorVars( 'retro', attributes, colors );

		expect( result ).toStrictEqual( {
			'--retro-warning-text-color': '#000011',
			'--retro-warning-background-color': '#aaffff',
		} );
	} );
} );
