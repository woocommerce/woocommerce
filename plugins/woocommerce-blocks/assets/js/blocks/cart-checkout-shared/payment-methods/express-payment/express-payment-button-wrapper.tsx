const ExpressPaymentButtonWrapper = ( {
	children,
}: {
	children: React.ReactNode;
} ) => {
	const defaultStyle = {
		width: '100px',
		maxWidth: '100%',
		borderRadius: '10px !important',
		color: 'red',
	};

	return <div style={ defaultStyle }>{ children }</div>;
};
export default ExpressPaymentButtonWrapper;
