export type HeaderProps = {
	onTabSelect: ( tabId: string | null ) => void;
	productType?: string;
};

export interface Image {
	id: number;
	src: string;
	name: string;
	alt: string;
}
