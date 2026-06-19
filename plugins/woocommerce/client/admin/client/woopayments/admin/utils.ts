export const getSettingsPaymentsProviderRouteUrl = ( path: string ) => {
	const adminUrl = window.wcSettings?.adminUrl || '';
	const separator = adminUrl.endsWith( '/' ) || adminUrl === '' ? '' : '/';
	const queryIndex = path.indexOf( '?' );
	const routePath = queryIndex === -1 ? path : path.slice( 0, queryIndex );
	const routeQuery = queryIndex === -1 ? '' : path.slice( queryIndex + 1 );

	return `${ adminUrl }${ separator }admin.php?page=wc-settings&tab=checkout&path=${ encodeURIComponent(
		routePath
	) }${ routeQuery ? `&${ routeQuery }` : '' }`;
};
