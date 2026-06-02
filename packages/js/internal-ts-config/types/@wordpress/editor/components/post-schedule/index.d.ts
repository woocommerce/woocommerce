declare module '@wordpress/editor/build-types/components/post-schedule' {
	/**
	 * Renders the PostSchedule component. It allows the user to schedule a post.
	 *
	 * @param {Object}   props         Props.
	 * @param {Function} props.onClose Function to close the component.
	 *
	 * @return {React.ReactNode} The rendered component.
	 */
	export default function PostSchedule(props: {
	    onClose: Function;
	}): React.ReactNode;
	export function PrivatePostSchedule({ onClose, showPopoverHeaderActions, isCompact, }: {
	    onClose: any;
	    showPopoverHeaderActions: any;
	    isCompact: any;
	}): import("react").JSX.Element;
}
