# Experimental Setting Options Data Store

The setting options data store provides a centralized way to manage WooCommerce settings options, including groups and individual settings. It handles the state management for settings values, unsaved changes, and error states.

## Usage

```js
import { useSelect } from '@wordpress/data';
import { store as settingOptionsStore } from '@woocommerce/data';

function MySettingsComponent() {
  const settings = useSelect((select) => {
    const { getSettings } = select(settingOptionsStore);
    return getSettings('general');
  }, []);

  return (
    <div>
      {/* Use your settings here */}
    </div>
  );
}
```

## Actions

### `receiveGroups( groups: SettingsGroup[] )`

Receives and stores settings groups.

### `receiveSettings( groupId: string, settings: Setting[] )`

Receives and stores settings for a specific group.

### `editSetting( groupId: string, settingId: string, value: SettingValue )`

Updates a single setting value in the store state without saving to the server.

### `editSettings( groupId: string, updates: SettingUpdate[] | SettingsUpdateObject )`

Updates multiple settings at once in the store state without saving to the server. Accepts either:

- An array of `{ id, value }` objects
- An object with setting IDs as keys and values as values

### `setSaving( groupId: string, settingId: string | null, isSaving: boolean )`

Sets the saving state for a group or specific setting.

### `setError( groupId: string, settingId: string | null, error: Error )`

Sets the error state for a group or specific setting.

### `revertEditedSetting( groupId: string, settingId: string )`

Reverts changes for a specific setting.

### `revertEditedSettingsGroup( groupId: string )`

Reverts all changes in a settings group.

### `saveEditedSettingsGroup( groupId: string )`

Saves all edited settings in a settings group to the server.

### `saveEditedSetting( groupId: string, settingId: string )`

Saves a specific edited setting to the server.

### `saveSetting( groupId: string, settingId: string, value: SettingValue )`

Directly saves a setting value to the server without requiring it to be edited first.

### `saveSettingsGroup( groupId: string, updates: SettingUpdate[] | SettingsUpdateObject )`

Directly saves multiple settings to the server without requiring them to be edited first. Accepts either:

- An array of `{ id, value }` objects
- An object with setting IDs as keys and values as values

## Selectors

### `getGroups( state )`

Returns all settings groups.

### `getGroup( state, groupId )`

Returns a specific settings group.

### `getSettings( state, groupId )`

Returns all settings for a specific group.

### `getSetting( state, groupId, settingId )`

Returns a specific setting.

### `getSettingValue( state, groupId, settingId )`

Returns the current value of a specific setting. This selector will trigger the `getSetting` resolver if the setting is not in the state.

### `hasEditsForGroup( state, groupId )`

Returns whether a group has unsaved changes.

### `isSettingEdited( state, groupId, settingId )`

Returns whether a specific setting has unsaved changes.

### `isSaving( state, groupId, settingId? )`

Returns whether a group or specific setting is being saved.

### `getGroupError( state, groupId )`

Returns the error state for a specific group.

### `getSettingError( state, groupId, settingId )`

Returns the error state for a specific setting.
