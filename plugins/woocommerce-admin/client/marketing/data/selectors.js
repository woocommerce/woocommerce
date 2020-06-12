export function getInstalledPlugins( state ) {
	return state.installedPlugins;
}

export function getActivatingPlugins( state ) {
	return state.activatingPlugins;
}

export function getRecommendedPlugins( state, category ) {
	return state.recommendedPlugins[ category ] || [];
}

export function getBlogPosts( state, category ) {
	return state.blogPosts[ category ] || [];
}
