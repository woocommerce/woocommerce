// TODO: We should Download webfonts and host them locally on a site before launching CYS in Core.
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
};

// See https://developers.google.com/fonts/docs/css2
const FONT_API_BASE = 'https://fonts-api.wp.com/css2';

const FONT_AXIS = 'ital,wght@0,400;0,700;1,400;1,700';

export const FontFamiliesLoader = ( { fontFamilies, onLoad }: Props ) => {
	const params = useMemo(
		() =>
			new URLSearchParams( [
				...fontFamilies.map( ( { fontFamily } ) => [
					'family',
					`${ fontFamily }:${ FONT_AXIS }`,
				] ),
				[ 'display', 'swap' ],
			] ),
		fontFamilies
	);

	if ( ! params.getAll( 'family' ).length ) {
		return null;
	}

	return (
		<link
			rel="stylesheet"
			type="text/css"
			href={ `${ FONT_API_BASE }?${ params }` }
			onLoad={ onLoad }
			onError={ onLoad }
		/>
	);
};
