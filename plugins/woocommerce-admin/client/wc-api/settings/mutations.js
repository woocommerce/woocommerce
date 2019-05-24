/** @format */

const updateSettings = operations => settingFields => {
	const resourceKey = 'settings';
	Object.keys( settingFields ).map( group =>
		operations.update( [ resourceKey + '/' + group ], {
			[ resourceKey + '/' + group ]: settingFields[ group ],
		} )
	);
};

export default {
	updateSettings,
};
