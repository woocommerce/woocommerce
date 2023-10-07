/**
 * Internal dependencies
 */
import { ColorPalette } from './types';

export const ColorPalettes = ( {
	colorPalettes,
}: {
	colorPalettes: ColorPalette[];
} ) => {
	return (
		<ul className="theme-card__color-palettes">
			{ colorPalettes.map( ( colorPalette ) => (
				<li
					key={ colorPalette.title }
					style={ {
						background:
							'linear-gradient(to right, ' +
							colorPalette.secondary + 
							' 0px, ' +
							colorPalette.secondary +
							' 50%, ' +
							colorPalette.primary +
							' 50%, ' +
							colorPalette.primary +
							' 100%' +
							')',
					} }
				></li>
			) ) }
		</ul>
	);
};
