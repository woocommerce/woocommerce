/**
 * Internal dependencies
 */
import { ColorPalette } from './types';

export const ColorPalettes = ( {
	colorPalettes,
	totalPalettes,
}: {
	colorPalettes: ColorPalette[];
	totalPalettes: number;
} ) => {
	let extra = null;

	if ( totalPalettes > 4 ) {
		extra = <li className="more_palettes">+{ totalPalettes - 4 }</li>;
	}

	return (
		<ul className="theme-card__color-palettes">
			{ colorPalettes.map( ( colorPalette ) => (
				<li
					key={ colorPalette.title }
					style={ {
						background:
							'linear-gradient(to right, ' +
							colorPalette.primary +
							' 0px, ' +
							colorPalette.primary +
							' 50%, ' +
							colorPalette.secondary +
							' 50%, ' +
							colorPalette.secondary +
							' 100%' +
							')',
					} }
				></li>
			) ) }
			{ extra }
		</ul>
	);
};
