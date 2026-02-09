/**
 * External dependencies
 */
import createSelector from 'rememo';

/**
 * Internal dependencies
 */
import type {
	SettingsState,
	SettingsGroup,
	Setting,
	SettingValue,
} from './types';

type SelectorOptions = {
	/**
	 * Whether to include edits in the returned value.
	 *
	 * @default false
	 */
	includeEdits?: boolean;
};

/**
 * Get all groups.
 *
 * @param {SettingsState} state - The current state of the settings.
 * @return {SettingsGroup[]} The list of all groups.
 */
export const getGroups = ( state: SettingsState ): SettingsGroup[] =>
	state.groups;

/**
 * Get a specific group by ID.
 *
 * @param {SettingsState} state   - The current state of the settings.
 * @param {string}        groupId - The ID of the group to get.
 * @return {SettingsGroup | undefined} The group if found, otherwise undefined.
 */
export const getGroup = (
	state: SettingsState,
	groupId: string
): SettingsGroup | undefined =>
	state.groups.find( ( group ) => group.id === groupId );

// Ensure we have a consistent empty object to return when there are no settings.
const EMPTY_OBJECT = {};

/**
 * Get all settings for a specific group.
 *
 * @param {SettingsState}   state   - The current state of the settings.
 * @param {string}          groupId - The ID of the group to get settings for.
 * @param {SelectorOptions} options - Options for the selector.
 * @return {Record<string, Setting>} The settings for the specified group.
 */
export const getSettings = createSelector(
	(
		state: SettingsState,
		groupId: string,
		options: SelectorOptions = { includeEdits: false }
	): Record< string, Setting > => {
		const groupSettings = state.settings[ groupId ];
		if ( ! groupSettings ) {
			return EMPTY_OBJECT;
		}

		// If we don't want edits, return original settings
		if ( options.includeEdits === false ) {
			return groupSettings;
		}

		const groupEdits = state.edits[ groupId ];
		if ( ! groupEdits ) {
			return groupSettings;
		}

		// Create a new object with all settings, applying edits where they exist
		return Object.keys( groupSettings ).reduce< Record< string, Setting > >(
			( result, settingId ) => {
				const setting = groupSettings[ settingId ];

				// If this setting has an edit, apply it
				if ( settingId in groupEdits ) {
					result[ settingId ] = {
						...setting,
						value: groupEdits[ settingId ],
					};
				} else {
					result[ settingId ] = setting;
				}

				return result;
			},
			{}
		);
	},
	(
		state: SettingsState,
		groupId: string,
		options: SelectorOptions = { includeEdits: false }
	) => [
		state.settings[ groupId ],
		state.edits[ groupId ],
		options.includeEdits,
	]
);

/**
 * Get a specific setting by ID.
 *
 * @param {SettingsState}   state     - The current state of the settings.
 * @param {string}          groupId   - The ID of the group to get settings for.
 * @param {string}          settingId - The ID of the setting to get.
 * @param {SelectorOptions} options   - Options for the selector.
 * @return {Setting | undefined} The setting if found, otherwise undefined.
 */
export const getSetting = createSelector(
	(
		state: SettingsState,
		groupId: string,
		settingId: string,
		options: SelectorOptions = { includeEdits: false }
	): Setting | undefined => {
		const groupSettings = state.settings[ groupId ];
		if ( ! groupSettings ) {
			return undefined;
		}

		const setting = groupSettings[ settingId ];
		if ( ! setting ) {
			return undefined;
		}

		// If we don't want edits, return original setting
		if ( options.includeEdits === false ) {
			return setting;
		}

		// If the setting is being edited, return the setting with the edited value
		const groupEdits = state.edits[ groupId ];
		if ( groupEdits && settingId in groupEdits ) {
			return {
				...setting,
				value: groupEdits[ settingId ],
			};
		}

		return setting;
	},
	(
		state: SettingsState,
		groupId: string,
		settingId: string,
		options: SelectorOptions = { includeEdits: false }
	) => [
		state.settings[ groupId ]?.[ settingId ],
		state.edits[ groupId ]?.[ settingId ],
		options.includeEdits,
	]
);

/**
 * Get the value of a specific setting.
 *
 * @param {SettingsState}   state     - The current state of the settings.
 * @param {string}          groupId   - The ID of the group to get settings for.
 * @param {string}          settingId - The ID of the setting to get.
 * @param {SelectorOptions} options   - Options for the selector.
 * @return {SettingValue | undefined} The value of the setting if found, otherwise undefined.
 */
export const getSettingValue = (
	state: SettingsState,
	groupId: string,
	settingId: string,
	options: SelectorOptions = { includeEdits: false }
): SettingValue | undefined => {
	// If we want edits and they exist, return the edited value
	if ( options.includeEdits !== false ) {
		const groupEdits = state.edits[ groupId ];
		if ( groupEdits && settingId in groupEdits ) {
			return groupEdits[ settingId ];
		}
	}

	// Otherwise return the original value
	const groupSettings = state.settings[ groupId ];
	if ( ! groupSettings ) {
		return undefined;
	}
	return groupSettings[ settingId ]?.value;
};

/**
 * Check if a specific setting has been edited.
 *
 * @param {SettingsState} state     - The current state of the settings.
 * @param {string}        groupId   - The ID of the group to get settings for.
 * @param {string}        settingId - The ID of the setting to check.
 */
export const isSettingEdited = (
	state: SettingsState,
	groupId: string,
	settingId: string
): boolean => {
	const groupEdits = state.edits[ groupId ];
	return !! groupEdits && settingId in groupEdits;
};

/**
 * Get all edited setting IDs for a specific group.
 *
 * @param {SettingsState} state   - The current state of the settings.
 * @param {string}        groupId - The ID of the group to get settings for.
 */
export const getEditedSettingIds = createSelector(
	( state: SettingsState, groupId: string ): string[] => {
		const groupEdits = state.edits[ groupId ];
		if ( ! groupEdits ) {
			return [];
		}
		return Object.keys( groupEdits );
	},
	( state: SettingsState, groupId: string ) => [ state.edits[ groupId ] ]
);

/**
 * Check if a specific group is currently being saved.
 *
 * @param {SettingsState} state   - The current state of the settings.
 * @param {string}        groupId - The ID of the group to check.
 */
export const isGroupSaving = (
	state: SettingsState,
	groupId: string
): boolean => !! state.isSaving.groups?.[ groupId ];

/**
 * Check if a specific setting is currently being saved.
 *
 * @param {SettingsState} state     - The current state of the settings.
 * @param {string}        groupId   - The ID of the group to check.
 * @param {string}        settingId - The ID of the setting to check.
 */
export const isSettingSaving = (
	state: SettingsState,
	groupId: string,
	settingId: string
): boolean => {
	const groupSaving = state.isSaving.settings?.[ groupId ];
	return !! groupSaving && !! groupSaving[ settingId ];
};

/**
 * Get the error for a specific group.
 *
 * @param {SettingsState} state   - The current state of the settings.
 * @param {string}        groupId - The ID of the group to get the error for.
 */
export const getGroupError = (
	state: SettingsState,
	groupId: string
): Record< string, unknown > | undefined => state.errors[ groupId ];

/**
 * Get the error for a specific setting.
 *
 * @param {SettingsState} state     - The current state of the settings.
 * @param {string}        groupId   - The ID of the group to get the error for.
 * @param {string}        settingId - The ID of the setting to get the error for.
 */
export const getSettingError = (
	state: SettingsState,
	groupId: string,
	settingId: string
): unknown => {
	const groupErrors = state.errors[ groupId ];
	if ( ! groupErrors ) {
		return undefined;
	}
	return groupErrors[ settingId ];
};

/**
 * Check if a specific group has any edits.
 *
 * @param {SettingsState} state   - The current state of the settings.
 * @param {string}        groupId - The ID of the group to check.
 */
export const hasEditsForGroup = (
	state: SettingsState,
	groupId: string
): boolean => {
	const groupEdits = state.edits[ groupId ];
	return !! groupEdits && Object.keys( groupEdits ).length > 0;
};
