/**
 * Internal dependencies
 */
import LightBulb from './images/lightbulb';

export const Image = ( { imageName }: { imageName: 'lightbulb' } ) => {
	switch ( imageName ) {
		case 'lightbulb':
			return <LightBulb />;
		default:
			return null;
	}
};
