export const hasTwoColumnLayout = (
	userPrefLayout: string,
	defaultHomescreenLayout: string
) => {
	return ( userPrefLayout || defaultHomescreenLayout ) === 'two_columns';
};
