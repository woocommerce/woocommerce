/**
 * External dependencies
 */
import type { Form } from '@wordpress/dataviews';

type FormEntry = NonNullable< Form[ 'fields' ] >[ number ];
export type FormGroup = Exclude< FormEntry, string >;

export interface SubPageLink {
	id: string;
	label: string;
}

/**
 * How a group opens as its own page, set in View Config as a `panel` layout with `openAs: { type: 'page' }`.
 * With `button: false`, the extension links to it itself.
 */
interface PageOpenAs {
	type: 'page';
	button?: boolean;
}

export interface ScreenLocation {
	/** The sub-pages leading to this location, outermost first. Empty on the main page. */
	subPages: FormGroup[];
	/** The part of the form to render at this location, with sub-pages replaced by links. */
	form: Form;
	/** Sub-pages that get a button where their group sits at this location. */
	subPageLinks: SubPageLink[];
	/** The fields edited at this location, which its Save and Discard cover. */
	fieldIds: string[];
}

/**
 * ID of the field that links to a sub-page, in place of the sub-page's group.
 */
export const subPageFieldId = ( id: string ) => `subpage:${ id }`;

const isGroup = ( entry: FormEntry ): entry is FormGroup =>
	typeof entry !== 'string' && Array.isArray( entry.children );

const labelOf = ( group: FormGroup ) => group.label ?? group.id;

// DataForm's types don't include `page`: WooCommerce reads it before the form reaches DataForm.
function pageOpenAs( entry: FormEntry ): PageOpenAs | undefined {
	if ( ! isGroup( entry ) ) {
		return undefined;
	}
	const layout = entry.layout as
		| { type?: string; openAs?: unknown }
		| undefined;
	const openAs = layout?.openAs as PageOpenAs | undefined;
	return layout?.type === 'panel' && openAs?.type === 'page'
		? openAs
		: undefined;
}

/**
 * Find a sub-page group at this level, without looking inside other sub-pages.
 */
function findSubPage(
	entries: FormEntry[],
	id: string
): FormGroup | undefined {
	for ( const entry of entries ) {
		if ( ! isGroup( entry ) ) {
			continue;
		}
		if ( pageOpenAs( entry ) ) {
			if ( entry.id === id ) {
				return entry;
			}
			continue;
		}
		const found = findSubPage( entry.children ?? [], id );
		if ( found ) {
			return found;
		}
	}
	return undefined;
}

/**
 * Replace sub-page groups with a field linking to them, or drop them when the extension links to them itself.
 */
function withoutSubPages(
	entries: FormEntry[],
	links: FormGroup[]
): FormEntry[] {
	return entries.flatMap< FormEntry >( ( entry ) => {
		if ( ! isGroup( entry ) ) {
			return [ entry ];
		}
		const openAs = pageOpenAs( entry );
		if ( openAs ) {
			if ( openAs.button === false ) {
				return [];
			}
			links.push( entry );
			return [ subPageFieldId( entry.id ) ];
		}
		return [
			{
				...entry,
				children: withoutSubPages( entry.children ?? [], links ),
			},
		];
	} );
}

/**
 * Collect the IDs of the fields in these entries. Groups aren't fields, so only their children count.
 */
function collectFieldIds( entries: FormEntry[] ): string[] {
	return entries.flatMap( ( entry ) => {
		if ( typeof entry === 'string' ) {
			return [ entry ];
		}
		return Array.isArray( entry.children )
			? collectFieldIds( entry.children )
			: [ entry.id ];
	} );
}

/**
 * Work out what to show for a path within a screen, such as `woopay` or `fraud-protection/rules`.
 *
 * The form is the main page. Groups with a `panel` layout that opens as a `page` open as their own
 * page, and each path segment opens a sub-page inside the previous one. Returns undefined when the
 * path doesn't match the form.
 */
export function resolveLocation(
	form: Form,
	path = ''
): ScreenLocation | undefined {
	let scope = form.fields ?? [];
	const trail: FormGroup[] = [];
	for ( const id of path.split( '/' ).filter( Boolean ) ) {
		const subPage = findSubPage( scope, id );
		if ( ! subPage ) {
			return undefined;
		}
		trail.push( subPage );
		scope = subPage.children ?? [];
	}

	const links: FormGroup[] = [];
	const fields = withoutSubPages( scope, links );
	const linkIds = new Set(
		links.map( ( group ) => subPageFieldId( group.id ) )
	);
	return {
		subPages: trail,
		form: { ...form, fields },
		subPageLinks: links.map( ( group ) => ( {
			id: group.id,
			label: labelOf( group ),
		} ) ),
		fieldIds: collectFieldIds( fields ).filter(
			( id ) => ! linkIds.has( id )
		),
	};
}
