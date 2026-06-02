declare module '@wordpress/editor/build-types/components/post-schedule/label' {
	/**
	 * Renders the PostScheduleLabel component.
	 *
	 * @param {Object} props Props.
	 *
	 * @return {React.ReactNode} The rendered component.
	 */
	export default function PostScheduleLabel(props: Object): React.ReactNode;
	/**
	 * Custom hook to get the label for post schedule.
	 *
	 * @param {Object}  options      Options for the hook.
	 * @param {boolean} options.full Whether to get the full label or not. Default is false.
	 *
	 * @return {string} The label for post schedule.
	 */
	export function usePostScheduleLabel({ full }?: {
	    full: boolean;
	}): string;
	export function getFullPostScheduleLabel(dateAttribute: any): string;
	export function getPostScheduleLabel(dateAttribute: any, { isFloating, now }?: {
	    isFloating?: boolean | undefined;
	    now?: Date | undefined;
	}): string;
}
