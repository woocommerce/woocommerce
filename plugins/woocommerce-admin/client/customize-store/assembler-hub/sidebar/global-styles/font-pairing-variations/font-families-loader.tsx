/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
// @ts-expect-error No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { store as coreStore } from '@wordpress/core-data';

/**
 * Internal dependencies
 */
import { FontFamily } from '~/customize-store/types/font';

type Props = {
	fontFamilies: Array< FontFamily >;
	onLoad?: () => void;
	preload?: boolean;
};

const isUrlEncoded = ( url: string ) => {
	if ( typeof url !== 'string' ) {
		return false;
	}
	return url !== decodeURIComponent( url );
};

const getDisplaySrcFromFontFace = ( input: string, urlPrefix: string ) => {
	if ( ! input ) {
		return;
	}

	// If it is a theme font, we need to make the url absolute
	if ( input.startsWith( 'file:.' ) && urlPrefix ) {
		const absoluteUrl = input.replace( 'file:.', urlPrefix );
		return ! isUrlEncoded( absoluteUrl )
			? encodeURI( absoluteUrl )
			: absoluteUrl;
	}

	return ! isUrlEncoded( input ) ? encodeURI( input ) : input;
};
export const FontFamiliesLoader = ( { fontFamilies, onLoad }: Props ) => {
	const { site, currentTheme } = useSelect( ( select ) => {
		return {
			// @ts-expect-error No types for this exist yet.
			site: select( coreStore ).getSite(),
			// @ts-expect-error No types for this exist yet.
			currentTheme: select( coreStore ).getCurrentTheme(),
		};
	} );

	const themeUrl =
		site?.url + '/wp-content/themes/' + currentTheme?.stylesheet;

	useEffect( () => {
		if ( ! Array.isArray( fontFamilies ) ) {
			return;
		}
		fontFamilies.forEach( async ( fontFamily ) => {
			fontFamily.fontFace?.forEach( async ( fontFace ) => {
				const srcFont = getDisplaySrcFromFontFace(
					fontFace.src,
					themeUrl
				);
				const dataSource = `url(${ srcFont })`;
				const newFont = new FontFace( fontFace.fontFamily, dataSource, {
					style: fontFace.fontStyle,
					weight: fontFace.fontWeight,
				} );

				const loadedFace = await newFont.load();

				document.fonts.add( loadedFace );
				if ( onLoad ) {
					onLoad();
				}
			} );
		} );
	}, [ fontFamilies, onLoad, themeUrl ] );

	return <></>;
};
