/**
 * Internal dependencies
 */
import { StagesFor } from './types';
import businessLocation from './stages/businessLocation';
export const useStages = ( stagesFor: StagesFor ) => {
	switch ( stagesFor ) {
		case 'intro':
		case 'userProfile':
		case 'businessInfo':
		case 'businessLocation':
			return businessLocation;
	}
};
