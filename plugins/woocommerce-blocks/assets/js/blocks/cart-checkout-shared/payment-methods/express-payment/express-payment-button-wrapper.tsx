// const ExpressPaymentButtonWrapper = ( {
// 	children,
// }: {
// 	children: React.ReactNode;
// } ) => {
// 	const defaultStyle = {
// 		width: '100px',
// 		maxWidth: '100%',
// 		borderRadius: '10px',
// 		background: 'red',
// 		overflow: 'hidden',
// 	};

// 	return (
// 		<>
// 			<div style={ defaultStyle }>{ children }</div>
// 			<div style={ defaultStyle }>{ children }</div>
// 		</>
// 	);
// };
// export default ExpressPaymentButtonWrapper;

const ExpressPaymentButtonWrapper = ( {
	children,
	size = 'default',
	theme = 'light',
}: {
	children: React.ReactNode;
	size?: 'small' | 'default' | 'large';
	theme?: 'light' | 'dark' | 'light-outline';
} ) => {
	const buttonHeight = {
		small: '40px',
		default: '48px',
		large: '56px',
	}[ size ];

	const themeStyles = {
		light: {
			backgroundColor: '#FFF',
			color: '#000',
			border: '1px solid #CCC',
		},
		dark: {
			backgroundColor: '#000',
			color: '#FFF',
		},
		'light-outline': {
			backgroundColor: '#FFF',
			color: '#000',
			border: '1px solid #AAA',
		},
	};

	const defaultStyle = {
		display: 'flex',
		justifyContent: 'center',
		alignItems: 'center',
		gap: '8px',
		minWidth: '120px',
		height: buttonHeight,
		borderRadius: '4px',
		overflow: 'hidden',
		...themeStyles[ theme ],
	};

	return <div style={ defaultStyle }>{ children }</div>;
};

export default ExpressPaymentButtonWrapper;
