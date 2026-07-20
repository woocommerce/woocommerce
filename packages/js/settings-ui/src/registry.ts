/**
 * Internal dependencies
 */
import { __resetWarnings, warn } from './diagnostics';
import { createLegacyEditControl } from './legacy-contract';
import type {
	SettingsUIField,
	SettingsExtensionRegistration,
	SettingsFieldComponent,
	SettingsFieldComponentDefinition,
	SettingsFieldComponentRegistration,
	SettingsFieldContext,
	SettingsFieldValidator,
	SettingsSaveHandler,
	SettingsVisibilityPredicate,
} from './types';

const registrations: SettingsExtensionRegistration[] = [];

const fieldComponentMapKeys = [
	'components',
	'fieldOverrides',
	'typeRenderers',
] as const;

const registrationMapKeys = [
	...fieldComponentMapKeys,
	'fieldVisibility',
	'groupVisibility',
	'saveHandlers',
] as const;

export type ResolvedEditControlRegistration =
	SettingsFieldComponentRegistration & {
		isLegacy: boolean;
	};

const isPlainRecord = ( value: unknown ): value is Record< string, unknown > =>
	typeof value === 'object' && value !== null && ! Array.isArray( value );

const isValidRegistration = (
	registration: unknown
): registration is SettingsExtensionRegistration => {
	if ( ! isPlainRecord( registration ) ) {
		return false;
	}

	const scope = registration.scope;
	if ( ! isPlainRecord( scope ) ) {
		return false;
	}

	if ( typeof scope.page !== 'string' || scope.page.length === 0 ) {
		return false;
	}

	if (
		typeof scope.section !== 'undefined' &&
		typeof scope.section !== 'string'
	) {
		return false;
	}

	return registrationMapKeys.every( ( key ) => {
		const value = registration[ key ];
		return typeof value === 'undefined' || isPlainRecord( value );
	} );
};

const hasSectionScope = ( scope: SettingsExtensionRegistration[ 'scope' ] ) =>
	typeof scope.section !== 'undefined';

const getScopeKey = ( scope: SettingsExtensionRegistration[ 'scope' ] ) =>
	`${ scope.page }::${
		hasSectionScope( scope ) ? scope.section || 'default' : '*'
	}`;

const scopeMatches = (
	registration: SettingsExtensionRegistration,
	context: SettingsFieldContext
) => {
	if ( registration.scope.page !== context.page ) {
		return false;
	}

	if ( ! hasSectionScope( registration.scope ) ) {
		return true;
	}

	return ( registration.scope.section ?? '' ) === ( context.section ?? '' );
};

const findInMatchingRegistrations = < T >(
	context: SettingsFieldContext,
	getValue: ( registration: SettingsExtensionRegistration ) => T | undefined
): T | undefined => {
	for ( let i = registrations.length - 1; i >= 0; i-- ) {
		const registration = registrations[ i ];
		if ( ! scopeMatches( registration, context ) ) {
			continue;
		}

		const value = getValue( registration );
		if ( typeof value !== 'undefined' ) {
			return value;
		}
	}

	return undefined;
};

const hasLegacyFieldComponents = (
	registration: SettingsExtensionRegistration
) =>
	fieldComponentMapKeys.some( ( key ) =>
		Object.values( registration[ key ] || {} ).some(
			( definition ) => typeof definition === 'function'
		)
	);

export const registerSettingsExtension = (
	registration: SettingsExtensionRegistration
) => {
	if ( ! isValidRegistration( registration ) ) {
		warn( 'Invalid settings extension registration payload.', {
			registration,
		} );
		return;
	}

	if ( hasLegacyFieldComponents( registration ) ) {
		const scopeKey = getScopeKey( registration.scope );
		warn(
			`Legacy settings field components were registered for scope "${ scopeKey }". See the Settings UI component migration guide. This bridge will be removed when Settings UI leaves its experimental feature flag.`,
			{ registration },
			`legacy-components:${ scopeKey }`
		);
	}

	registrations.push( registration );
};

export const __resetRegistry = () => {
	registrations.splice( 0 );
	__resetWarnings();
};

const resolveFieldComponentDefinition = (
	field: SettingsUIField,
	context: SettingsFieldContext,
	warnWhenMissing: boolean
): SettingsFieldComponentDefinition | undefined => {
	const componentName = field.component;
	const definition = [
		componentName
			? findInMatchingRegistrations(
					context,
					( registration ) =>
						registration.components?.[ componentName ]
			  )
			: undefined,
		findInMatchingRegistrations(
			context,
			( registration ) => registration.fieldOverrides?.[ field.id ]
		),
		findInMatchingRegistrations(
			context,
			( registration ) => registration.typeRenderers?.[ field.type ]
		),
	].find( ( candidate ) => typeof candidate !== 'undefined' );

	if ( ! definition && field.component && warnWhenMissing ) {
		warn( `Component "${ field.component }" is not registered.`, {
			field,
			context,
		} );
	}

	return definition;
};

/** Resolve a component using the contract shipped with WooCommerce 10.9. */
export const resolveFieldComponent = (
	field: SettingsUIField,
	context: SettingsFieldContext
): SettingsFieldComponent | undefined => {
	const definition = resolveFieldComponentDefinition( field, context, true );
	return typeof definition === 'function' ? definition : undefined;
};

export const resolveEditControlRegistration = (
	field: SettingsUIField,
	context: SettingsFieldContext
): ResolvedEditControlRegistration | undefined => {
	const definition = resolveFieldComponentDefinition( field, context, true );

	if ( typeof definition === 'function' ) {
		return {
			component: createLegacyEditControl( definition, field ),
			isLegacy: true,
		};
	}

	if (
		definition &&
		typeof definition.component === 'function' &&
		( typeof definition.validate === 'undefined' ||
			typeof definition.validate === 'function' )
	) {
		return { ...definition, isLegacy: false };
	}

	return undefined;
};

export const resolveFieldValidator = (
	field: SettingsUIField,
	context: SettingsFieldContext
): SettingsFieldValidator | undefined => {
	const definition = resolveFieldComponentDefinition( field, context, false );
	return typeof definition === 'object' ? definition.validate : undefined;
};

export const resolveFieldVisibilityPredicate = (
	fieldId: string,
	context: SettingsFieldContext
): SettingsVisibilityPredicate | undefined =>
	findInMatchingRegistrations(
		context,
		( registration ) => registration.fieldVisibility?.[ fieldId ]
	);

export const resolveGroupVisibilityPredicate = (
	groupId: string,
	context: SettingsFieldContext
): SettingsVisibilityPredicate | undefined =>
	findInMatchingRegistrations(
		context,
		( registration ) => registration.groupVisibility?.[ groupId ]
	);

export const resolveSaveHandler = (
	handler: string,
	context: SettingsFieldContext
): SettingsSaveHandler | undefined => {
	const saveHandler = findInMatchingRegistrations(
		context,
		( registration ) => registration.saveHandlers?.[ handler ]
	);

	if ( saveHandler ) {
		return saveHandler;
	}

	warn( `Save handler "${ handler }" is not registered.`, { context } );
	return undefined;
};
