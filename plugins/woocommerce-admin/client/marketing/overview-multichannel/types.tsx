export type Plugin = {
	slug: string;
	icon: string;
	name: string;
	description: string;
	status: string;
	docsUrl?: string;
	supportUrl?: string;
	settingsUrl?: string;
	dashboardUrl?: string;
};

export type UsePluginsType = {
	installed: Plugin[];
	activating: Plugin[];
};
