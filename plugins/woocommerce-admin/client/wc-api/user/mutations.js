const updateCurrentUserData = ( operations ) => ( userDataFields ) => {
	const resourceKey = 'current-user-data';
	operations.update( [ resourceKey ], { [ resourceKey ]: userDataFields } );
};

export default {
	updateCurrentUserData,
};
