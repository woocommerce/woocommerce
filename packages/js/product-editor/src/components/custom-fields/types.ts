export type CustomFieldsProps = React.DetailedHTMLProps<
	React.TableHTMLAttributes< HTMLTableElement >,
	HTMLTableElement
> & {
	renderActionButtonsWrapper?( buttons: React.ReactNode ): React.ReactNode;
};
