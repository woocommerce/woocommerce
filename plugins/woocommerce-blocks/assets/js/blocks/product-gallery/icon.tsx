const Icon = () => (
	<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none">
		<rect width="24" height="24" fill="none" rx="2" />
		<path
			fill="#000"
			d="M19 3H5c-.6 0-1 .4-1 1v7c0 .5.4 1 1 1h14c.5 0 1-.4 1-1V4c0-.6-.4-1-1-1ZM5.5 10.5v-.4l1.8-1.3 1.3.8c.3.2.7.2.9-.1L11 8.1l2.4 2.4H5.5Zm13 0h-2.9l-4-4c-.3-.3-.8-.3-1.1 0L8.9 8l-1.2-.8c-.3-.2-.6-.2-.9 0l-1.3 1V4.5h13v6Z"
		/>
		<mask id="a" fill="#fff">
			<rect width="6" height="5.5" x="4" y="14.5" rx="1" />
		</mask>
		<rect
			width="6"
			height="5.5"
			x="4"
			y="14.5"
			stroke="#000"
			strokeWidth="3"
			mask="url(#a)"
			rx="1"
		/>
		<mask id="b" fill="#fff">
			<rect width="6" height="5.5" x="11" y="14.5" rx="1" />
		</mask>
		<rect
			width="6"
			height="5.5"
			x="11"
			y="14.5"
			stroke="#000"
			strokeWidth="3"
			mask="url(#b)"
			rx="1"
		/>
	</svg>
);

export default Icon;
