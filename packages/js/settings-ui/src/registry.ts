/**
 * Internal dependencies
 */
import { warn } from './diagnostics';
import type {
	SettingsUIField,
	SettingsEditControl,
	SettingsExtensionRegistration,
	SettingsFieldContext,
	SettingsRegionComponent,
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
	'regions',
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

const getScopeKey = ( scope: SettingsExtensionRegistration[ 'scope' ] ) =>
	`${ scope.page }::${
		hasSectionScope( scope ) ? scope.section || 'default' : '*'
	}`;

const pruneReplacedEntries = ( incoming: SettingsExtensionRegistration ) => {
	let hasDuplicateKeys = false;
	for ( let i = registrations.length - 1; i >= 0; i-- ) {
		let existing = registrations[ i ];
		if (
			existing.scope.page !== incoming.scope.page ||
			existing.scope.section !== incoming.scope.section
		) {
			continue;
		}

		for ( const key of registrationMapKeys ) {
			const entries = existing[ key ];
			const replacements = incoming[ key ];
			if ( ! entries || ! replacements ) {
				continue;
			}

			const remaining = Object.entries( entries ).filter( ( [ id ] ) => {
				const replaced =
					Object.prototype.hasOwnProperty.call( replacements, id ) &&
					typeof replacements[ id ] !== 'undefined';
				hasDuplicateKeys ||= replaced;
				return ! replaced;
			} );
			existing = {
				...existing,
				[ key ]: Object.fromEntries( remaining ),
			};
		}

		if (
			registrationMapKeys.some(
				( key ) => Object.keys( existing[ key ] ?? {} ).length > 0
			)
		) {
			registrations[ i ] = existing;
		} else {
			registrations.splice( i, 1 );
		}
	}
	return hasDuplicateKeys;
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

	const hasDuplicateKeys = pruneReplacedEntries( registration );

	if ( hasDuplicateKeys ) {
		warn(
			`Registration already exists for scope "${ getScopeKey(
				registration.scope
			) }". Replacing the conflicting entries.`,
			{ registration }
		);
	}

	registrations.push( registration );
};

export const __resetRegistry = () => {
	registrations.splice( 0 );
};

export const resolveFieldComponent = (
	field: SettingsUIField,
	context: SettingsFieldContext
): SettingsEditControl | undefined => {
	const componentName = field.component;
	const component = componentName
		? findInMatchingRegistrations(
				context,
				( registration ) => registration.components?.[ componentName ]
		  )
		: undefined;

	const resolvedComponent =
		component ??
		findInMatchingRegistrations(
			context,
			( registration ) => registration.fieldOverrides?.[ field.id ]
		) ??
		findInMatchingRegistrations(
			context,
			( registration ) => registration.typeRenderers?.[ field.type ]
		);

	if ( resolvedComponent ) {
		return resolvedComponent;
	}

	if ( field.component ) {
		warn( `Component "${ field.component }" is not registered.`, {
			field,
			context,
		} );
	}

	return undefined;
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

export const resolveRegionComponent = (
	component: string,
	context: SettingsFieldContext
): SettingsRegionComponent | undefined => {
	const region = findInMatchingRegistrations(
		context,
		( registration ) => registration.regions?.[ component ]
	);

	if ( region ) {
		return region;
	}

	warn( `Region component "${ component }" is not registered.`, {
		context,
	} );
	return undefined;
};
