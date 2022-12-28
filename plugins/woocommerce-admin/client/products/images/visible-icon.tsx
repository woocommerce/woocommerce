export default function VisibleIcon( {
	width = 24,
	height = 24,
	...props
}: React.SVGProps< SVGSVGElement > ) {
	return (
		<svg
			{ ...props }
			width={ width }
			height={ height }
			viewBox="0 0 24 24"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
			aria-hidden="true"
		>
			<path
				d="M20.1091 11.54C20.3396 11.8116 20.3396 12.1884 20.1091 12.46C19.4144 13.2781 18.266 14.4899 16.8343 15.4921C15.397 16.4982 13.7359 17.25 11.9999 17.25C10.2638 17.25 8.60268 16.4982 7.1654 15.4921C5.73376 14.4899 4.58533 13.2781 3.89066 12.46C3.6601 12.1884 3.6601 11.8116 3.89066 11.54C4.58533 10.7219 5.73376 9.51006 7.1654 8.50792C8.60268 7.50184 10.2638 6.75 11.9999 6.75C13.7359 6.75 15.397 7.50184 16.8343 8.50792C18.266 9.51006 19.4144 10.7219 20.1091 11.54Z"
				stroke="currentColor"
				strokeWidth="1.5"
				strokeLinejoin="round"
			/>
			<circle
				cx="11.9999"
				cy="11.9999"
				r="2.67857"
				stroke="currentColor"
				strokeWidth="1.5"
			/>
		</svg>
	);
}
