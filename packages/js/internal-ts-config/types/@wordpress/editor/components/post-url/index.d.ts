declare module '@wordpress/editor/build-types/components/post-url' {
	/**
	 * Renders the `PostURL` component.
	 *
	 * @example
	 * ```jsx
	 * <PostURL />
	 * ```
	 *
	 * @param {{ onClose: () => void }} props         The props for the component.
	 * @param {() => void}              props.onClose Callback function to be executed when the popover is closed.
	 *
	 * @return {React.ReactNode} The rendered PostURL component.
	 */
	export default function PostURL({ onClose }: {
	    onClose: () => void;
	}): React.ReactNode;
}
