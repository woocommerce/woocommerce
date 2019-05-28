/** @format */

const updateProfileItems = operations => fields => {
	const resourceKey = 'onboarding-profile';
	operations.update( [ resourceKey ], {
		[ resourceKey ]: fields,
	} );
};

export default {
	updateProfileItems,
};
