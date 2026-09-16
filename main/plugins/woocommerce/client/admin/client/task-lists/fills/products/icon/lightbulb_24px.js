const Lightbulb = () => {
	return (
		<svg
			width="25"
			height="24"
			viewBox="0 0 25 24"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
		>
			<mask
				id="mask0_1133_132689"
				style={ { maskType: 'alpha' } }
				maskUnits="userSpaceOnUse"
				x="5"
				y="2"
				width="15"
				height="20"
			>
				<path
					fillRule="evenodd"
					clipRule="evenodd"
					d="M12.5 2C8.64 2 5.5 5.14 5.5 9C5.5 11.38 6.69 13.47 8.5 14.74V17C8.5 17.55 8.95 18 9.5 18H15.5C16.05 18 16.5 17.55 16.5 17V14.74C18.31 13.47 19.5 11.38 19.5 9C19.5 5.14 16.36 2 12.5 2ZM9.5 21C9.5 21.55 9.95 22 10.5 22H14.5C15.05 22 15.5 21.55 15.5 21V20H9.5V21ZM14.5 13.7L15.35 13.1C16.7 12.16 17.5 10.63 17.5 9C17.5 6.24 15.26 4 12.5 4C9.74 4 7.5 6.24 7.5 9C7.5 10.63 8.3 12.16 9.65 13.1L10.5 13.7V16H14.5V13.7Z"
					fill="white"
				/>
			</mask>
			<g mask="url(#mask0_1133_132689)">
				<rect x="0.5" width="24" height="24" fill="#757575" />
			</g>
		</svg>
	);
};

export default Lightbulb;
