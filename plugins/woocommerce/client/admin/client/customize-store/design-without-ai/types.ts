export type DesignWithoutAIStateMachineContext = {
	startLoadingTime: number | null;
	apiCallLoader: {
		hasErrors: boolean;
	};
	isFontLibraryAvailable: boolean;
	isPTKPatternsAPIAvailable: boolean;
	isBlockTheme: boolean;
};

export interface Theme {
	_links: {
		'wp:user-global-styles': { href: string }[];
	};
}
