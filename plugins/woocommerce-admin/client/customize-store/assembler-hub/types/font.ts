export type FontToInstall = {
	[ slug: string ]: {
		fontFamily: string;
		fontWeights: Array< string >;
		fontStyles: Array< string >;
	};
};

export type Font = {
	fontFace: Array< FontFace >;
	fontFamily: string;
	name: string;
	slug: string;
};

export type FontFace = {
	fontFamily: string;
	fontStretch?: string;
	fontStyle: string;
	fontWeight: string;
	src: Array< string >;
};
