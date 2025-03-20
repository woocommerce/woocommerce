export function getInstalledPlugins( state ) {
	return state.installedPlugins;
}

export function getActivatingPlugins( state ) {
	return state.activatingPlugins;
}

export function getRecommendedPlugins( state, category ) {
	return state.recommendedPlugins[ category ] || [];
}

export function getMiscRecommendations( state ) {
	return state.miscRecommendations;
}

export function getBlogPosts( state, category ) {
	return state.blogPosts[ category ] || [];
}

export function getBlogPostsError( state, category ) {
	return state.errors.blogPosts && state.errors.blogPosts[ category ];
}
