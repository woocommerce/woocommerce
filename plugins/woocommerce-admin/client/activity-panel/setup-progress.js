export const SetupProgress = ( {
	setupTasksComplete,
	setupCompletePercent,
} ) => (
	<svg
		className="woocommerce-layout__activity-panel-tab-icon setup-progress"
		viewBox="0 0 24 24"
	>
		<path
			d="M 11.985 22.747 C 17.878 22.747 22.655 17.924 22.655 11.974 C 22.655 6.022 17.878 1.197 11.985 1.197 C 6.09 1.197 1.312 6.022 1.312 11.974 C 1.312 17.924 6.09 22.747 11.985 22.747 Z"
			stroke="#DCDCDE"
			strokewidth="2"
			fill="currentColor"
		/>
		<path
			d="M4 12V12C4 16.4183 7.58172 20 12 20V20C16.4183 20 20 16.4183 20 12V12C20 7.58172 16.4183 4 12 4V4"
			strokewidth="2"
			strokelinecap="round"
		/>
		<path
			className="setup-progress-slice"
			transform="matrix(-0.034188, 0, 0, 0.034134, 37.882862, -8.768827)"
			d="M 522 607 A 237 237 0 0 1 759 370 L 759 607 Z"
			fill={ setupTasksComplete > 0 ? 'currentColor' : 'white' }
		/>
		<path
			className="setup-progress-slice"
			transform="matrix(-0.034188, 0, 0, -0.034134, 37.878132, 32.640987)"
			d="M 522 607 A 237 237 0 0 1 759 370 L 759 607 Z"
			fill={ setupCompletePercent >= 50 ? 'currentColor' : 'white' }
		/>
		<path
			className="setup-progress-slice"
			transform="matrix(0.034188, 0, 0, -0.034134, -13.99084, 32.643505)"
			d="M 522 607 A 237 237 0 0 1 759 370 L 759 607 Z"
			fill={ setupCompletePercent >= 75 ? 'currentColor' : 'white' }
		/>
		<path
			className="setup-progress-slice"
			transform="matrix(0.034188, 0, 0, 0.034134, -13.986106, -8.771348)"
			d="M 522 607 A 237 237 0 0 1 759 370 L 759 607 Z"
			fill="white"
		/>
	</svg>
);
