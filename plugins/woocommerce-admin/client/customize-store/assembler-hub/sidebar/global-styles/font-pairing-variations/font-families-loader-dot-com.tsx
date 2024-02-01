// TODO: When wp.com will upgrade to WordPress 6.5, we should remove this logic and use the FontFamiliesLoader component instead.
// Load font families from wp.com.

/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';

export type FontFamily = {
	fontFamily: string;
	name: string;
	slug: string;
};

type Props = {
	fontFamilies: FontFamily[];
	onLoad?: () => void;
	preload?: boolean;
};

// See https://developers.google.com/fonts/docs/css2
const FONT_API_BASE = 'https://fonts-api.wp.com/css2';
// this is the actual host that the .woff files are at, the above is the one for the .css files with the @font-face declarations
const FONT_HOST = 'https://fonts.wp.com'; // used for preconnecting so that fonts can get loaded faster

const FONT_AXIS = 'ital,wght@0,400;0,700;1,400;1,700';

export const FontFamiliesLoaderDotCom = ( {
	fontFamilies,
	onLoad,
	preload = false,
}: Props ) => {
	const params = useMemo(
		() =>
			new URLSearchParams( [
				...fontFamilies.map( ( { fontFamily } ) => [
					'family',
					`${ fontFamily }:${ FONT_AXIS }`,
				] ),
				[ 'display', 'swap' ],
			] ),
		[ fontFamilies ]
	);

	if ( ! params.getAll( 'family' ).length ) {
		return null;
	}

	return (
		<>
			{ preload ? <link rel="preconnect" href={ FONT_HOST } /> : null }
			<link
				rel={ preload ? 'preload' : 'stylesheet' }
				type="text/css"
				as={ preload ? 'style' : undefined }
				href={ `${ FONT_API_BASE }?${ params }` }
				onLoad={ onLoad }
				onError={ onLoad }
			/>
		</>
	);
};
