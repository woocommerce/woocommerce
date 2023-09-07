// placeholder xstate async service that returns a set of theme cards

export const fetchThemeCards = async () => {
	return [
		{
			name: 'Twenty Twenty One',
			description: 'The default theme for WordPress.',
		},
		{
			name: 'Twenty Twenty',
			description: 'The previous default theme for WordPress.',
		},
	];
};
