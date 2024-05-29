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
import {
	fetchExperimentAssignment,
	fetchExperimentAssignmentWithAuth,
	canTrack,
} from './assignment';
import { getAnonId, initializeAnonId } from './anon';
declare global {
	interface Window {
		wcTracks: {
			isEnabled: boolean;
			enable?: ( cb: () => void ) => void;
		};
		_wca: {
			push?: ( cb: () => void ) => void;
		};
	}
}

export const initializeExPlat = (): void => {
	if ( canTrack ) {
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

export const { loadExperimentAssignment, dangerouslyGetExperimentAssignment } =
	exPlatClient;

export const { useExperiment, Experiment, ProvideExperimentData } =
	createExPlatClientReactHelpers( exPlatClient );

// Create another auth client that send request to wpcom as auth user.
const exPlatClientWithAuth = createExPlatClient( {
	fetchExperimentAssignment: fetchExperimentAssignmentWithAuth,
	getAnonId,
	logError,
	isDevelopmentMode,
} );

export const {
	loadExperimentAssignment: loadExperimentAssignmentWithAuth,
	dangerouslyGetExperimentAssignment:
		dangerouslyGetExperimentAssignmentWithAuth,
} = exPlatClientWithAuth;

export const {
	useExperiment: useExperimentWithAuth,
	Experiment: ExperimentWithAuth,
	ProvideExperimentData: ProvideExperimentDataWithAuth,
} = createExPlatClientReactHelpers( exPlatClientWithAuth );
