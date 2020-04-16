export const getActivePlugins = ( state ) => {
	return state.active || [];
};

export const getInstalledPlugins = ( state ) => {
	return state.installed || [];
};

export const isPluginsRequesting = ( state, selector ) => {
	return state.requesting[ selector ] || false;
};

export const getPluginsError = ( state, selector ) => {
	return state.errors[ selector ] || false;
};

export const isJetpackConnected = ( state ) => state.jetpackConnection;

export const getJetpackConnectUrl = ( state, query ) => {
	return state.jetpackConnectUrls[ query.redirect_url ];
};
