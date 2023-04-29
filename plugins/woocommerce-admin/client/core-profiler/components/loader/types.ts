export type StagesFor =
	| 'intro'
	| 'userProfile'
	| 'businessInfo'
	| 'businessLocation';

export type Stage = {
	title: string;
	image: string | JSX.Element;
	progress?: number;
	paragraphs: Array< {
		label: string;
		text: string;
		duration?: number;
	} >;
};

export type Stages = Array< Stage >;
