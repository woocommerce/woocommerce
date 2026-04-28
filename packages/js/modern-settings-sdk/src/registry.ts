/**
 * Internal dependencies
 */
import { warn } from './diagnostics';
import type {
	ModernSettingsField,
	SettingsExtensionRegistration,
	SettingsFieldComponent,
	SettingsFieldContext,
	SettingsRegionComponent,
	SettingsSaveHandler,
	SettingsVisibilityPredicate,
} from './types';

const registrations: SettingsExtensionRegistration[] = [];

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

const warnOnDuplicateKeys = (
	registration: SettingsExtensionRegistration,
	key: keyof Pick<
		SettingsExtensionRegistration,
		| 'components'
		| 'fieldOverrides'
		| 'typeRenderers'
		| 'fieldVisibility'
		| 'groupVisibility'
		| 'saveHandlers'
		| 'regions'
	>
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

		incomingKeys.forEach( ( entryKey ) => {
			if (
				Object.prototype.hasOwnProperty.call(
					existingEntries,
					entryKey
				)
			) {
				warn(
					`Registration for "${ entryKey }" in "${ key }" already exists for scope "${ scopeKey }". The latest registration will take precedence.`,
					{ registration }
				);
			}
		} );
	}
};

export const registerSettingsExtension = (
	registration: SettingsExtensionRegistration
) => {
	(
		[
			'components',
			'fieldOverrides',
			'typeRenderers',
			'fieldVisibility',
			'groupVisibility',
			'saveHandlers',
			'regions',
		] as const
	 ).forEach( ( key ) => warnOnDuplicateKeys( registration, key ) );

	registrations.push( registration );
};

export const resolveFieldComponent = (
	field: ModernSettingsField,
	context: SettingsFieldContext
): SettingsFieldComponent | undefined => {
	for ( let i = registrations.length - 1; i >= 0; i-- ) {
		const registration = registrations[ i ];
		if ( ! scopeMatches( registration, context ) ) {
			continue;
		}

		if ( field.component ) {
			const namedComponent = registration.components?.[ field.component ];
			if ( namedComponent ) {
				return namedComponent;
			}
		}

		const fieldOverride = registration.fieldOverrides?.[ field.id ];
		if ( fieldOverride ) {
			return fieldOverride;
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
	window.wcModernSettings = {
		...( window.wcModernSettings || {} ),
		registerSettingsExtension,
	};
}
