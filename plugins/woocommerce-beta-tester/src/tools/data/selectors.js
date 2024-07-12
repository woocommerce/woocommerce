export function getCurrentlyRunning( state ) {
	return state.currentlyRunning;
}

export function getMessages( state ) {
	return state.messages;
}

export function getStatus( state ) {
	return state.status;
}

export function getCommandParams( state ) {
	return state.params;
}

export function getCronJobs( state ) {
	return state.cronJobs;
}

export function getIsEmailDisabled( state ) {
	return state.isEmailDisabled;
}

export function getDBUpdateVersions( state ) {
	return state.dbUpdateVersions;
}

export function getLoggingLevels( state ) {
	return state.loggingLevels;
}

export function getBlockTemplateLoggingThreshold( state ) {
	return state.params.updateBlockTemplateLoggingThreshold.threshold;
}

export function getComingSoonMode( state ) {
	return state.params.updateComingSoonMode.mode;
}
