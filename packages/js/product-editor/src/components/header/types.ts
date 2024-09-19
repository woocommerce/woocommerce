export type HeaderProps = {
	onTabSelect: ( tabId: string ) => void;
	productType?: string;
	selectedTab: string | null;
};

export interface Image {
	id: number;
	src: string;
	name: string;
	alt: string;
}
