export type Step = {
	title: string;
	image: string | JSX.Element;
	progress?: number;
	paragraphs: Array< {
		label: string;
		text: string;
		duration?: number;
	} >;
};

export type Steps = Array< Step >;
