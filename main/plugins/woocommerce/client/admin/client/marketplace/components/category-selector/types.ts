export type Category = {
	readonly slug: string;
	readonly label: string;
	selected: boolean;
};

export type CategoryAPIItem = {
	readonly slug: string;
	readonly label: string;
};
