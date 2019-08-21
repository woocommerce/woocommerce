/** @format */

const updateProfileItems = operations => fields => {
	const resourceKey = 'onboarding-profile';
	operations.update( [ resourceKey ], {
		[ resourceKey ]: fields,
	} );
};

const installPlugins = operations => plugins => {
	const resourceKey = 'plugin-install';
	operations.update( [ resourceKey ], {
		[ resourceKey ]: plugins,
	} );
};

const activatePlugins = operations => plugins => {
	const resourceKey = 'plugin-activate';
	operations.update( [ resourceKey ], {
		[ resourceKey ]: plugins,
	} );
};

export default {
	activatePlugins,
	installPlugins,
	updateProfileItems,
};
