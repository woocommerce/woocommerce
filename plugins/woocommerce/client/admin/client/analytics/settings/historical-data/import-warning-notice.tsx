/**
 * External dependencies
 */
import { ReactNode } from 'react';
import { Button, Notice } from '@wordpress/components';

interface ImportWarningNoticeProps {
	/** Extra class name applied to the notice. */
	className?: string;
	/** Notice body. Rendered inside a paragraph. */
	message: ReactNode;
	/** Action button label. */
	buttonLabel: string;
	/** Optional label shown on the button while the action is running. */
	busyLabel?: string;
	/** Whether the action is currently running. */
	isBusy: boolean;
	/** Action handler. */
	onAction: () => void;
}

/**
 * Presentational warning notice with a single busy-aware action button.
 *
 * Shared by the failed-imports and refund double-count notices in the analytics
 * settings "Import historical data" section. Holds no state of its own — count
 * logic, data fetching, and copy live in the wrapping components.
 */
function ImportWarningNotice( {
	className,
	message,
	buttonLabel,
	busyLabel,
	isBusy,
	onAction,
}: ImportWarningNoticeProps ) {
	return (
		<Notice
			className={ className }
			status="warning"
			isDismissible={ false }
		>
			<p>{ message }</p>
			<Button
				variant="secondary"
				isBusy={ isBusy }
				disabled={ isBusy }
				aria-disabled={ isBusy }
				onClick={ onAction }
			>
				{ isBusy && busyLabel ? busyLabel : buttonLabel }
			</Button>
		</Notice>
	);
}

export default ImportWarningNotice;
