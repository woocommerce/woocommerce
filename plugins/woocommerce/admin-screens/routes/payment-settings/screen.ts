/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from 'react';
import type { Field, Form } from '@wordpress/dataviews';
import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

export type SettingsRecord = Record< string, unknown >;

// Settings entities have no record key (`key: false`), but core-data's types require one.
export const NO_KEY = undefined as unknown as string;

export interface PaymentSettingsScreen {
	id: string;
	title: string;
	entity: { kind: string; name: string; baseURL: string };
	classicUrl: string | null;
}

export interface ScreenDefinition {
	fields: Field< SettingsRecord >[];
	form: Form;
	errors: Error[];
}

declare global {
	interface Window {
		wcPaymentSettingsScreen?: PaymentSettingsScreen;
	}
}

export interface FieldsResponse {
	fields: Field< SettingsRecord >[];
	script_modules: { id: string; fields: string[] }[];
}

/**
 * Get the screen that PHP passed to the page, when it matches the route.
 */
export function getScreen( id?: string ): PaymentSettingsScreen | undefined {
	const screen = window.wcPaymentSettingsScreen;
	return screen && screen.id === id ? screen : undefined;
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
async function loadFields( kind: string, name: string ) {
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
 * Load a screen's fields from the Fields API and its form from View Config.
 */
export async function loadDefinition(
	screen: PaymentSettingsScreen
): Promise< ScreenDefinition > {
	const { kind, name } = screen.entity;
	const [ fields, form ] = await Promise.all( [
		loadFields( kind, name ),
		apiFetch< { form?: Form } >( {
			path: addQueryArgs( '/wp/v2/view-config', { kind, name } ),
		} ),
	] );
	return {
		fields: fields.fields,
		form: form.form ?? { fields: [] },
		errors: fields.errors,
	};
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

/**
 * Load a screen's definition once, reporting a failed request as an error.
 */
export function useScreenDefinition( screen: PaymentSettingsScreen ) {
	const [ state, setState ] = useState< {
		definition?: ScreenDefinition;
		error?: Error;
	} >( {} );

	useEffect( () => {
		let current = true;
		loadDefinition( screen ).then(
			( definition ) => {
				if ( current ) {
					setState( { definition } );
				}
			},
			( error: unknown ) => {
				if ( current ) {
					setState( { error: toError( error ) } );
				}
			}
		);
		return () => {
			current = false;
		};
	}, [ screen ] );

	return state;
}
