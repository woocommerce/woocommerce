export const Icon = () => (
	<svg
		width="18"
		height="18"
		viewBox="0 0 18 18"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M6.22448 1.5L1.5 6.81504V11.7072L5.12953 9.06066C5.38061 8.87758 5.71858 8.86829 5.97934 9.0373L8.90601 10.9342L12.4772 7.46225C12.7683 7.17925 13.2317 7.17925 13.5228 7.46225L16.5 10.3568V2C16.5 1.72386 16.2761 1.5 16 1.5H6.22448ZM1.5 13.5636V16C1.5 16.2761 1.72386 16.5 2 16.5H16C16.2761 16.5 16.5 16.2761 16.5 16V12.4032L16.4772 12.4266L13 9.04603L9.52279 12.4266C9.27191 12.6706 8.88569 12.7086 8.59206 12.5183L5.59643 10.5766L1.5 13.5636ZM0 2C0 0.89543 0.895431 0 2 0H16C17.1046 0 18 0.895431 18 2V16C18 17.1046 17.1046 18 16 18H2C0.89543 18 0 17.1046 0 16V2Z"
			fill="#1E1E1E"
		/>
	</svg>
);

export const PrevIcon = ( { className }: { className: string } ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="8"
		height="12"
		fill="none"
		className={ className }
	>
		<path
			fill="currentColor"
			fillRule="evenodd"
			d="M6.445 12.005.986 6 6.445-.005l1.11 1.01L3.014 6l4.54 4.995-1.109 1.01Z"
			clipRule="evenodd"
		/>
	</svg>
);

export const NextIcon = ( { className }: { className: string } ) => (
	<svg
		xmlns="http://www.w3.org/2000/svg"
		width="8"
		height="12"
		fill="none"
		className={ className }
	>
		<path
			fill="currentColor"
			fillRule="evenodd"
			d="M1.555-.004 7.014 6l-5.459 6.005-1.11-1.01L4.986 6 .446 1.005l1.109-1.01Z"
			clipRule="evenodd"
		/>
	</svg>
);
