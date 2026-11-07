/**
 * External dependencies
 */
import warning from '@wordpress/warning';

export interface PageCrumb {
	label: string;
	to?: string;
}

/**
 * Entity name of a settings page: its path after the area, such as `cheque` or
 * `cheque/customer_messages` for a sub-page.
 */
export const getPageName = ( page = '', subPath = '' ) =>
	[ page, ...subPath.split( '/' ) ].filter( Boolean ).join( '/' );

/**
 * Router path to a settings page.
 */
const pagePath = ( section: string, name: string ) =>
	`/settings/${ section }/${ name }`;

/**
 * Breadcrumbs for a settings page and the pages above it, each labelled by its entity.
 * The last crumb is the current page, so it has no link.
 */
export function getPageCrumbs(
	section: string,
	name: string,
	getLabel: ( pageName: string ) => string | undefined
): PageCrumb[] {
	const segments = name.split( '/' );
	return segments.map( ( segment, index ) => {
		const pageName = segments.slice( 0, index + 1 ).join( '/' );
		const label = getLabel( pageName );
		if ( label === undefined ) {
			warning(
				`The settings page "${ pageName }" isn't registered as an entity, so its breadcrumb shows its URL segment.`
			);
		}
		const crumb = { label: label ?? segment };
		return index === segments.length - 1
			? crumb
			: { ...crumb, to: pagePath( section, pageName ) };
	} );
}
