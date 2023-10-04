/**
 * Internal dependencies
 */
import { FONT_PAIRINGS } from './sidebar/global-styles/font-pairing-variations/constants';
import { FontFamiliesLoader } from './sidebar/global-styles/font-pairing-variations/font-families-loader';

export const PreloadFonts = () => {
	const allFontChoices = FONT_PAIRINGS.map(
		( fontPair ) => fontPair?.settings?.typography?.fontFamilies?.theme
	);
	const fontFamilies = allFontChoices.map( ( fontPair ) => {
		return [
			...fontPair.map( ( font ) => {
				return {
					...font,
					name: font.fontFamily,
				};
			} ),
		];
	} );

	return (
		<>
			{ fontFamilies.map( ( fontPair ) => (
				<FontFamiliesLoader
					fontFamilies={ fontPair }
					key={ fontPair.map( ( font ) => font.slug ).join( '-' ) }
					preload
				/>
			) ) }
		</>
	);
};
