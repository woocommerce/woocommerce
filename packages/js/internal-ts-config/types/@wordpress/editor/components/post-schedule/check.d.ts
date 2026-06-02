declare module '@wordpress/editor/build-types/components/post-schedule/check' {
	/**
	 * Wrapper component that renders its children only if post has a publish action.
	 *
	 * @param {Object}          props          Props.
	 * @param {React.ReactNode} props.children Children to be rendered.
	 *
	 * @return {React.ReactNode} - The component to be rendered or null if there is no publish action.
	 */
	export default function PostScheduleCheck({ children }: {
	    children: React.ReactNode;
	}): React.ReactNode;
}
