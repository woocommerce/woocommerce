/**
 * Internal dependencies
 */
import { getFontFamiliesAndFontFaceToInstall } from '../fonts';

describe( 'font families and font face installation', () => {
	test( 'should not get any font family if no new font family is provided', () => {
		const fontCollection = {
			slug: 'unknown',
			font_families: [],
		};
		const result = getFontFamiliesAndFontFaceToInstall(
			fontCollection as never,
			[]
		);

		expect( result.fontFacesToInstall ).toStrictEqual( [] );
		expect( result.fontFamiliesWithFontFacesToInstall ).toStrictEqual( [] );
	} );

	test( 'should get new font families from collection if they are not installed', () => {
		const fontCollection = {
			slug: 'inter',
			font_families: [
				{ font_family_settings: { slug: 'inter', fontFace: [] } },
			],
		};
		const result = getFontFamiliesAndFontFaceToInstall(
			fontCollection as never,
			[]
		);

		expect( result.fontFacesToInstall ).toStrictEqual( [] );
		expect( result.fontFamiliesWithFontFacesToInstall ).toStrictEqual( [
			{ slug: 'inter', fontFace: [] },
		] );
	} );

	test( 'should get a new font face to install from font families', () => {
		const fontCollection = {
			slug: 'inter',
			font_families: [
				{ font_family_settings: { slug: 'inter', fontFace: [] } },
			],
		};
		const fontFamilyId = 'ID';
		const fontWeight = '500';
		const installedFontFamilies = [
			{
				id: fontFamilyId,
				font_family_settings: { slug: 'inter' },
				font_face: [ { fontWeight } ],
			},
		];
		const result = getFontFamiliesAndFontFaceToInstall(
			fontCollection as never,
			installedFontFamilies as never
		);

		expect( result.fontFacesToInstall ).toStrictEqual( [
			{ fontFamilyId, fontWeight },
		] );
		expect( result.fontFamiliesWithFontFacesToInstall ).toStrictEqual( [] );
	} );
} );
