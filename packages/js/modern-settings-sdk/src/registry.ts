/**
 * Internal dependencies
 */
import { warn } from './diagnostics';
import type {
	ModernSettingsField,
	SettingsExtensionRegistration,
	SettingsFieldComponent,
	SettingsFieldContext,
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

export const registerSettingsExtension = (
	registration: SettingsExtensionRegistration
) => {
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

if ( typeof window !== 'undefined' ) {
	window.wcModernSettings = {
		...( window.wcModernSettings || {} ),
		registerSettingsExtension,
	};
}
