export type PluginHeaderItemModalProps = {
	children?:
		| React.ReactNode
		| ( ( props: {
				isOpen: boolean;
				setOpen: ( isOpen: boolean ) => void;
		  } ) => React.ReactNode );
	icon?: JSX.Element;
	label?: string;
	title: string;
};

export type PluginHeaderItemPopoverProps = {
	children?:
		| React.ReactNode
		| ( ( props: {
				isVisible: boolean;
				setVisible: ( isVisible: boolean ) => void;
		  } ) => React.ReactNode );
	icon?: JSX.Element;
	label?: string;
};
