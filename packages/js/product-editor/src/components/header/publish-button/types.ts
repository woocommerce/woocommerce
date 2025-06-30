export type PublishButtonProps = {
	productType?: string;
	isMenuButton?: boolean;
	isPrePublishPanelVisible?: boolean;
	visibleTab?: string | null;
	disabled?: boolean;
	onClick?: ( event: React.MouseEvent< HTMLElement > ) => void;
};
