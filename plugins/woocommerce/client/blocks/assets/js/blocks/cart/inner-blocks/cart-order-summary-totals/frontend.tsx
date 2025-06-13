const FrontendBlock = ( {
	children,
	className = '',
}: {
	children?: JSX.Element | JSX.Element[];
	className?: string;
} ) => {
	return <div className={ className }>{ children }</div>;
};

export default FrontendBlock;
