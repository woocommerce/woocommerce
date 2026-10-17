/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import type { Field, Form } from '@wordpress/dataviews';
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

export type SettingsRecord = Record< string, unknown >;

// Settings entities have no record key (`key: false`), but core-data's types require one.
export const NO_KEY = undefined as unknown as string;

// An absolute URL in `to` makes Core's Breadcrumbs render a plain link, which reloads the page.
export interface Breadcrumb {
	label: string;
	to?: string;
}

export interface SettingsScreen {
	title: string;
	breadcrumbs: Breadcrumb[];
	entity: { kind: string; name: string; baseURL: string };
}

export interface ScreenDefinition {
	fields: Field< SettingsRecord >[];
	form: Form;
	errors: Error[];
}

export interface ResolutionSelectors {
	hasFinishedResolution: ( selector: string, args: unknown[] ) => boolean;
	getResolutionError: ( selector: string, args: unknown[] ) => unknown;
}

export interface FieldsResponse {
	fields: Field< SettingsRecord >[];
	script_modules: { id: string; fields: string[] }[];
}

/**
 * Apply each loaded module's field parts in the server's order, collecting modules that failed.
 */
export function applyFieldModules(
	response: FieldsResponse,
	modules: PromiseSettledResult< {
		default?: Record< string, Partial< Field< SettingsRecord > > >;
	} >[]
) {
	const fields = response.fields.map( ( field ) => ( { ...field } ) );
	const errors: Error[] = [];

	response.script_modules.forEach( ( module, index ) => {
		const result = modules[ index ];
		if ( ! result || result.status === 'rejected' ) {
			errors.push(
				new Error(
					sprintf(
						/* translators: %s: script module ID. */
						__(
							'Some settings could not be loaded (%s).',
							'woocommerce'
						),
						module.id
					)
				)
			);
			return;
		}
		const parts = result.value.default ?? {};
		module.fields.forEach( ( id ) => {
			const field = fields.find( ( item ) => item.id === id );
			if ( field && parts[ id ] ) {
				Object.assign( field, parts[ id ] );
			}
		} );
	} );

	return { fields, errors };
}

/**
 * Load registered fields and apply the JavaScript parts from their script modules.
 *
 * Modules load in parallel but apply in the server's order, so the result doesn't depend on
 * which import finishes first. A module that fails to load is reported instead of skipped silently.
 */
export async function loadFields( kind: string, name: string ) {
	const response = await apiFetch< FieldsResponse >( {
		path: addQueryArgs( '/wp/v2/fields', { kind, name } ),
	} );
	const modules = await Promise.allSettled(
		response.script_modules.map(
			( module ) => import( /* webpackIgnore: true */ module.id )
		)
	);
	return applyFieldModules( response, modules );
}

/**
 * Turn a rejection into an Error. apiFetch rejects with a plain `{ code, message }` object.
 */
export function toError(
	error: unknown,
	fallback: string = __( 'Unable to load settings.', 'woocommerce' )
): Error {
	if ( error instanceof Error ) {
		return error;
	}
	if (
		typeof error === 'object' &&
		error !== null &&
		'message' in error &&
		typeof error.message === 'string' &&
		error.message !== ''
	) {
		return new Error( error.message );
	}
	return new Error( fallback );
}
