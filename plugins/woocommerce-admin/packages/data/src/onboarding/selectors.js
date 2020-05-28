export const getProfileItems = ( state ) => {
	return state.profileItems || {};
};

export const getOnboardingError = ( state, selector ) => {
	return state.errors[ selector ] || false;
};

export const isOnboardingRequesting = ( state, selector ) => {
	return state.requesting[ selector ] || false;
};
