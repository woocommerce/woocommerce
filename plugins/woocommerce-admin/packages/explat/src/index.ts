/**
 * External dependencies
 */
import { createExPlatClient } from '@automattic/explat-client';
import createExPlatClientReactHelpers from '@automattic/explat-client-react-helpers';

/**
 * Internal dependencies
 */
import { isDevelopmentMode } from './utils';
import { logError } from './error';
import { fetchExperimentAssignment } from './assignment';
import { getAnonId, initializeAnonId } from './anon';

declare global {
	interface Window {
		wcTracks: {
			isEnabled: boolean;
		};
	}
}

export const initializeExPlat = (): void => {
	if ( window.wcTracks?.isEnabled ) {
		initializeAnonId().catch( ( e ) => logError( { message: e.message } ) );
	}
};

initializeExPlat();

const exPlatClient = createExPlatClient( {
	fetchExperimentAssignment,
	getAnonId,
	logError,
	isDevelopmentMode,
} );

export const {
	loadExperimentAssignment,
	dangerouslyGetExperimentAssignment,
} = exPlatClient;
const exPlatClientReactHelpers = createExPlatClientReactHelpers( exPlatClient );
export const {
	useExperiment,
	Experiment,
	ProvideExperimentData,
} = exPlatClientReactHelpers;
