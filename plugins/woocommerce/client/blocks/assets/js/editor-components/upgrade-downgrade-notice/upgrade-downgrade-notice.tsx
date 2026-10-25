/**
 * External dependencies
 */
import { Notice } from '@wordpress/components';
import { clsx } from 'clsx';

/**
 * Internal dependencies
 */
import type { UpgradeDowngradeNoticeProps } from './types';
import './style.scss';

export function UpgradeDowngradeNotice( {
	children,
	className,
	actionLabel,
	onActionClick,
	status = 'info',
	...props
}: UpgradeDowngradeNoticeProps ) {
	return (
		<Notice
			{ ...props }
			status={ status }
			politeness="polite"
			className={ clsx(
				'wc-block-editor-components-upgrade-downgrade-notice',
				className
			) }
			actions={ [
				{
					label: actionLabel,
					onClick: onActionClick,
					noDefaultClasses: true,
					variant: 'link',
				},
			] }
		>
			<div className="wc-block-editor-components-upgrade-downgrade-notice__text">
				{ children }
			</div>
		</Notice>
	);
}
