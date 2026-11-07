/**
 * Read a setting from a gateway record, whether it holds the endpoint's `{ value }` or an edit.
 */
export function getSettingValue( setting: unknown ): unknown {
	if (
		typeof setting === 'object' &&
		setting !== null &&
		! Array.isArray( setting ) &&
		'value' in setting
	) {
		return setting.value;
	}
	return setting;
}

/**
 * Edit one setting of a gateway record.
 *
 * Every setting goes into the edit as a plain value, which is what the endpoint takes, so the
 * save sends exactly the edited record's settings.
 */
export function editSetting(
	settings: Record< string, unknown >,
	key: string,
	value: unknown
): { settings: Record< string, unknown > } {
	return {
		settings: {
			...Object.fromEntries(
				Object.entries( settings ).map( ( [ name, setting ] ) => [
					name,
					getSettingValue( setting ),
				] )
			),
			[ key ]: value,
		},
	};
}
