/**
 * External dependencies
 */
import GridiconNotice from 'gridicons/dist/notice';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import { RegisteredChannel } from '~/marketing/types';
import { iconSize } from './iconSize';
import './IssueStatus.scss';

type IssueStatusPropsType = {
	registeredChannel: RegisteredChannel;
};

const issueStatusClassName = 'woocommerce-marketing-issue-status';

export const IssueStatus: React.FC< IssueStatusPropsType > = ( {
	registeredChannel,
} ) => {
	if ( registeredChannel.issueType === 'error' ) {
		return (
			<div
				className={ classnames(
					issueStatusClassName,
					`${ issueStatusClassName }__error`
				) }
			>
				<GridiconNotice size={ iconSize } />
				{ registeredChannel.issueText }
			</div>
		);
	}

	if ( registeredChannel.issueType === 'warning' ) {
		return (
			<div
				className={ classnames(
					issueStatusClassName,
					`${ issueStatusClassName }__warning`
				) }
			>
				<GridiconNotice size={ iconSize } />
				{ registeredChannel.issueText }
			</div>
		);
	}

	return (
		<div className={ issueStatusClassName }>
			{ registeredChannel.issueText }
		</div>
	);
};
