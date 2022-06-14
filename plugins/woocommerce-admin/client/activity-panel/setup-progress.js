export const SetupProgress = ( {
	setupTasksComplete,
	setupCompletePercent,
} ) => (
	<svg
		className="woocommerce-layout__activity-panel-tab-icon setup-progress"
		viewBox="0 0 25 25"
	>
		<path
			className="setup-progress-ring"
			d="M 12.476 23.237 C 18.369 23.237 23.146 18.414 23.146 12.464 C 23.146 6.512 18.369 1.687 12.476 1.687 C 6.581 1.687 1.803 6.512 1.803 12.464 C 1.803 18.414 6.581 23.237 12.476 23.237 Z"
		/>
		<path
			className="setup-progress-slice"
			transform="matrix(-0.034188, 0, 0, 0.034134, 38.373184, -8.278505)"
			d="M 522 607 A 237 237 0 0 1 759 370 L 759 607 Z"
			fill={ setupTasksComplete > 0 ? 'currentColor' : 'white' }
		/>
		<path
			className="setup-progress-slice"
			transform="matrix(-0.034188, 0, 0, -0.034134, 38.368454, 33.13131)"
			d="M 522 607 A 237 237 0 0 1 759 370 L 759 607 Z"
			fill={ setupCompletePercent >= 50 ? 'currentColor' : 'white' }
		/>
		<path
			className="setup-progress-slice"
			transform="matrix(0.034188, 0, 0, -0.034134, -13.500516, 33.133827)"
			d="M 522 607 A 237 237 0 0 1 759 370 L 759 607 Z"
			fill={ setupCompletePercent >= 75 ? 'currentColor' : 'white' }
		/>
		<path
			className="setup-progress-slice"
			transform="matrix(0.034188, 0, 0, 0.034134, -13.495783, -8.281025)"
			d="M 522 607 A 237 237 0 0 1 759 370 L 759 607 Z"
			fill="white"
		/>
	</svg>
);
