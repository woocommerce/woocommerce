/**
 * Internal dependencies
 */
import { warn } from './diagnostics';
import type {
	SettingsUIField,
	SettingsExtensionRegistration,
	SettingsFieldComponent,
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

type RegistrationMapKey = ( typeof registrationMapKeys )[ number ];

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

const scopeMatches = (
	registration: SettingsExtensionRegistration,
	context: SettingsFieldContext
) => {
	if ( registration.scope.page !== context.page ) {
		return false;
	}

	return (
		! registration.scope.section ||
		registration.scope.section === context.section
	);
};

const getScopeKey = ( scope: SettingsExtensionRegistration[ 'scope' ] ) =>
	`${ scope.page }::${ scope.section || 'default' }`;

const hasDuplicateScopeAndKeys = (
	registration: SettingsExtensionRegistration,
	key: RegistrationMapKey
) => {
	const entries = registration[ key ];
	if ( ! entries ) {
		return;
	}

	const incomingKeys = Object.keys( entries );
	if ( incomingKeys.length === 0 ) {
		return;
	}

	const scopeKey = getScopeKey( registration.scope );
	for ( const existing of registrations ) {
		if ( getScopeKey( existing.scope ) !== scopeKey ) {
			continue;
		}

		const existingEntries = existing[ key ];
		if ( ! existingEntries ) {
			continue;
		}

		if (
			incomingKeys.some( ( entryKey ) =>
				Object.prototype.hasOwnProperty.call(
					existingEntries,
					entryKey
				)
			)
		) {
			return true;
		}
	}

	return false;
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

	const hasDuplicateKeys = registrationMapKeys.some( ( key ) =>
		hasDuplicateScopeAndKeys( registration, key )
	);

	if ( hasDuplicateKeys ) {
		warn(
			`Registration already exists for scope "${ getScopeKey(
				registration.scope
			) }". Replacing the existing registration.`,
			{ registration }
		);
		for ( let i = registrations.length - 1; i >= 0; i-- ) {
			if (
				getScopeKey( registrations[ i ].scope ) ===
				getScopeKey( registration.scope )
			) {
				registrations.splice( i, 1 );
			}
		}
	}

	registrations.push( registration );
};

export const __resetRegistry = () => {
	registrations.splice( 0 );
};

export const resolveFieldComponent = (
	field: SettingsUIField,
	context: SettingsFieldContext
): SettingsFieldComponent | undefined => {
	if ( field.component ) {
		for ( let i = registrations.length - 1; i >= 0; i-- ) {
			const registration = registrations[ i ];
			if ( ! scopeMatches( registration, context ) ) {
				continue;
			}

			const namedComponent = registration.components?.[ field.component ];
			if ( namedComponent ) {
				return namedComponent;
			}
		}
	}

	for ( let i = registrations.length - 1; i >= 0; i-- ) {
		const registration = registrations[ i ];
		if ( ! scopeMatches( registration, context ) ) {
			continue;
		}

		const fieldOverride = registration.fieldOverrides?.[ field.id ];
		if ( fieldOverride ) {
			return fieldOverride;
		}
	}

	for ( let i = registrations.length - 1; i >= 0; i-- ) {
		const registration = registrations[ i ];
		if ( ! scopeMatches( registration, context ) ) {
			continue;
		}

		const typeRenderer = registration.typeRenderers?.[ field.type ];
		if ( typeRenderer ) {
			return typeRenderer;
		}
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
): SettingsVisibilityPredicate | undefined => {
	for ( let i = registrations.length - 1; i >= 0; i-- ) {
		const registration = registrations[ i ];
		if ( ! scopeMatches( registration, context ) ) {
			continue;
		}

		const predicate = registration.fieldVisibility?.[ fieldId ];
		if ( predicate ) {
			return predicate;
		}
	}

	return undefined;
};

export const resolveGroupVisibilityPredicate = (
	groupId: string,
	context: SettingsFieldContext
): SettingsVisibilityPredicate | undefined => {
	for ( let i = registrations.length - 1; i >= 0; i-- ) {
		const registration = registrations[ i ];
		if ( ! scopeMatches( registration, context ) ) {
			continue;
		}

		const predicate = registration.groupVisibility?.[ groupId ];
		if ( predicate ) {
			return predicate;
		}
	}

	return undefined;
};

export const resolveSaveHandler = (
	handler: string,
	context: SettingsFieldContext
): SettingsSaveHandler | undefined => {
	for ( let i = registrations.length - 1; i >= 0; i-- ) {
		const registration = registrations[ i ];
		if ( ! scopeMatches( registration, context ) ) {
			continue;
		}

		const saveHandler = registration.saveHandlers?.[ handler ];
		if ( saveHandler ) {
			return saveHandler;
		}
	}

	warn( `Save handler "${ handler }" is not registered.`, { context } );
	return undefined;
};

export const resolveRegionComponent = (
	component: string,
	context: SettingsFieldContext
): SettingsRegionComponent | undefined => {
	for ( let i = registrations.length - 1; i >= 0; i-- ) {
		const registration = registrations[ i ];
		if ( ! scopeMatches( registration, context ) ) {
			continue;
		}

		const region = registration.regions?.[ component ];
		if ( region ) {
			return region;
		}
	}

	warn( `Region component "${ component }" is not registered.`, {
		context,
	} );
	return undefined;
};

if ( typeof window !== 'undefined' ) {
	window.wcSettingsUI = {
		...( window.wcSettingsUI || {} ),
		registerSettingsExtension,
	};
}
