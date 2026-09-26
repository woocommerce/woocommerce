export type InstalledPlugin = {
	slug: string;
	icon: string;
	name: string;
	description: string;
	status: 'installed' | 'activated' | 'configured';
	docsUrl?: string;
	supportUrl?: string;
	settingsUrl?: string;
	dashboardUrl?: string;
};
