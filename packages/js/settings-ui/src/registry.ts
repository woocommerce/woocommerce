/**
 * Internal dependencies
 */
import { warn } from './diagnostics';
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

const registrationMapKeys = [
	'components',
	'fieldOverrides',
	'typeRenderers',
	'fieldVisibility',
	'groupVisibility',
	'saveHandlers',
] as const;

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

export const registerSettingsExtension = (
	registration: SettingsExtensionRegistration
) => {
	if ( ! isValidRegistration( registration ) ) {
		warn( 'Invalid settings extension registration payload.', {
			registration,
		} );
		return;
	}

	registrations.push( registration );
};

export const __resetRegistry = () => {
	registrations.splice( 0 );
};

const normalizeFieldComponentDefinition = (
	definition: SettingsFieldComponentDefinition | undefined
): SettingsFieldComponentRegistration | undefined => {
	if ( typeof definition === 'function' ) {
		return { component: definition };
	}

	if (
		definition &&
		typeof definition.component === 'function' &&
		( typeof definition.validate === 'undefined' ||
			typeof definition.validate === 'function' )
	) {
		return definition;
	}

	return undefined;
};

const resolveFieldComponentRegistration = (
	field: SettingsUIField,
	context: SettingsFieldContext,
	warnWhenMissing: boolean
): SettingsFieldComponentRegistration | undefined => {
	const componentName = field.component;
	const resolved = [
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
	]
		.map( normalizeFieldComponentDefinition )
		.find( ( definition ) => definition );

	if ( ! resolved && field.component && warnWhenMissing ) {
		warn( `Component "${ field.component }" is not registered.`, {
			field,
			context,
		} );
	}

	return resolved;
};

export const resolveFieldComponent = (
	field: SettingsUIField,
	context: SettingsFieldContext
): SettingsFieldComponent | undefined =>
	resolveFieldComponentRegistration( field, context, true )?.component;

export const resolveFieldValidator = (
	field: SettingsUIField,
	context: SettingsFieldContext
): SettingsFieldValidator | undefined =>
	resolveFieldComponentRegistration( field, context, false )?.validate;

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
