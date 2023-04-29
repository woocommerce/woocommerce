export type Stage = {
	title: string;
	progress?: number;
	image?: string | JSX.Element;
	paragraphs: Array< {
		label: string;
		text: string;
		duration?: number;
	} >;
};

export type Stages = Array< Stage >;
