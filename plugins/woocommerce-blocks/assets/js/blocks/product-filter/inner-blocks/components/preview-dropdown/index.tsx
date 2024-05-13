/**
 * Preview dropdown component for collection filter editor blocks that mimics the markup of interactivity dropdown.
 */
export const PreviewDropdown = ( { placeholder }: { placeholder: string } ) => {
	return (
		<div className="wc-interactivity-dropdown">
			<div className="wc-interactivity-dropdown__dropdown">
				<div
					className="wc-interactivity-dropdown__dropdown-selection"
					tabIndex={ 0 }
				>
					<span className="wc-interactivity-dropdown__placeholder">
						{ placeholder }
					</span>

					<span className="wc-interactivity-dropdown__svg-container">
						<svg
							viewBox="0 0 24 24"
							xmlns="http://www.w3.org/2000/svg"
							width="30"
							height="30"
						>
							<path d="M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z"></path>
						</svg>
					</span>
				</div>
			</div>
		</div>
	);
};
