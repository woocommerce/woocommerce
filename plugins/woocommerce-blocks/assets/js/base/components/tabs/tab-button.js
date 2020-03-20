const TabButton = ( {
	tabId,
	onClick,
	children,
	selected,
	ariaLabel,
	...rest
} ) => {
	return (
		<button
			role="tab"
			type="button"
			tabIndex={ selected ? 0 : -1 }
			aria-selected={ selected }
			aria-label={ ariaLabel }
			id={ tabId }
			onClick={ onClick }
			{ ...rest }
		>
			<span className="wc-block-components-tabs__item-content">
				{ children }
			</span>
		</button>
	);
};

export default TabButton;
