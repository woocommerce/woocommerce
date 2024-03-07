export type AdviceCardProps = React.DetailedHTMLProps<
	React.HTMLAttributes< HTMLDivElement >,
	HTMLDivElement
> & {
	tip?: string;
	isDismissible?: boolean;
	dismissPreferenceId?: string;
	onDismiss?: () => void;
};
