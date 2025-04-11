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
	...props
}: UpgradeDowngradeNoticeProps ) {
	return (
		<Notice
			{ ...props }
			className={ clsx(
				'wc-block-editor-components-upgrade-downgrade-notice',
				className
			) }
			actions={ [
				{
					label: actionLabel,
					onClick: onActionClick,
					noDefaultClasses: true,
					// @ts-expect-error the 'variant' prop does exists.
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
