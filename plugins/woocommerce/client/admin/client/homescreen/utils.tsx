export const hasTwoColumnLayout = (
	userPrefLayout: string,
	defaultHomescreenLayout: string,
	isSetupTaskListActive: boolean
) => {
	const hasTwoColumnContent =
		! isSetupTaskListActive || window.wcAdminFeatures.analytics;

	return (
		( userPrefLayout || defaultHomescreenLayout ) === 'two_columns' &&
		hasTwoColumnContent
	);
};
