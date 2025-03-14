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

### `updateSetting( groupId: string, settingId: string, value: SettingValue, options?: { save?: boolean } )`

Updates a single setting value. If `options.save` is true, the setting will be immediately saved to the server.

### `updateSettings( groupId: string, updates: SettingUpdate[] | SettingsUpdateObject, options?: { save?: boolean } )`

Updates multiple settings at once. If `options.save` is true, the settings will be immediately saved to the server. Accepts either:

- An array of `{ id, value }` objects
- An object with setting IDs as keys and values as values

### `setSaving( groupId: string, settingId: string | null, isSaving: boolean )`

Sets the saving state for a group or specific setting.

### `setError( groupId: string, settingId: string | null, error: Error )`

Sets the error state for a group or specific setting.

### `revertSetting( groupId: string, settingId: string )`

Reverts changes for a specific setting.

### `revertGroup( groupId: string )`

Reverts all changes in a settings group.

### `saveSettingsGroup( groupId: string )`

Saves all changes in a settings group to the server.

### `saveSetting( groupId: string, settingId: string )`

Saves changes for a specific setting to the server.

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

### `hasEdits( state, groupId )`

Returns whether a group has unsaved changes.

### `isSettingEdited( state, groupId, settingId )`

Returns whether a specific setting has unsaved changes.

### `isSaving( state, groupId, settingId? )`

Returns whether a group or specific setting is being saved.

### `getGroupError( state, groupId )`

Returns the error state for a specific group.

### `getSettingError( state, groupId, settingId )`

Returns the error state for a specific setting.
