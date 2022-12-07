export function getCesSurveyQueue( state ) {
	return state.queue;
}

export function getVisibleCESModalData( state ) {
	return state.showCESModal ? state.cesModalData : undefined;
}
