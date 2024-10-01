export type PostPublishSectionProps = {
	postType: string;
};

export type PostPublishTitleProps = {
	productType: string;
};

export type CopyButtonProps = {
	text: string;
	onCopy: () => void;
	children: JSX.Element;
};
