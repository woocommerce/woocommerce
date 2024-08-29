const FrontendBlock = ( {
	children,
	className = '',
}: {
	children?: JSX.Element | JSX.Element[];
	className?: string;
} ): JSX.Element | null => {
	return <div className={ className }>{ children }</div>;
};

export default FrontendBlock;
