/** @format */

const updateSettings = operations => settingFields => {
	const resourceKey = 'settings';
	operations.update( [ resourceKey ], { [ resourceKey ]: settingFields } );
};

export default {
	updateSettings,
};
