export type FontFamiliesToInstall = {
	[ slug: string ]: {
		fontFamily: string;
		fontWeights: Array< string >;
		fontStyles: Array< string >;
	};
};

export type FontFamily = {
	fontFace: Array< FontFace >;
	fontFamily: string;
	name: string;
	slug: string;
	preview: string;
};

export type FontFace = {
	fontFamily: string;
	fontStretch?: string;
	fontStyle: string;
	fontWeight: string;
	src: Array< string > | string;
};
