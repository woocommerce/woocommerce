export type CesSurvey = {
	action: string;
	showDescription: boolean;
	title: string;
	description: string;
	noticeLabel: string;
	firstQuestion: string;
	secondQuestion: string;
	icon: string;
	pageNow: string;
	adminPage: string;
	onSubmitLabel: string;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	onSubmitNoticeProps: Record< string, any >;
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	props: Record< string, any >;
	tracksProps: Record< string, string | number >;
	getExtraFieldsToBeShown?: (
		extraFieldsValues: { [ key: string ]: string },
		setExtraFieldsValues: ( values: { [ key: string ]: string } ) => void,
		errors: Record< string, string > | undefined
	) => JSX.Element;
	validateExtraFields?: ( values: { [ key: string ]: string } ) => {
		[ key: string ]: string;
	};
};
