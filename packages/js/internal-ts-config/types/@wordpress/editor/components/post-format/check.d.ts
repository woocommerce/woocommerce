declare module '@wordpress/editor/build-types/components/post-format/check' {
	/**
	 * Component check if there are any post formats.
	 *
	 * @param {Object}          props          The component props.
	 * @param {React.ReactNode} props.children The child elements to render.
	 *
	 * @return {React.ReactNode} The rendered component or null if post formats are disabled.
	 */
	export default function PostFormatCheck({ children }: {
	    children: React.ReactNode;
	}): React.ReactNode;
}
