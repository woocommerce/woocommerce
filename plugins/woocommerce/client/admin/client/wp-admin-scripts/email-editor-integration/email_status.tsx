import React from 'react';
import { __ } from '@wordpress/i18n';
import { __experimentalHStack as HStack, Icon } from '@wordpress/components';
import { EMAIL_STATUSES } from '../../settings-email/settings-email-listing-status';

export function EmailStatus() {
	return (
		<div>
			<strong>Email Status:</strong>
			{EMAIL_STATUSES.map((status) => (
				<HStack key={status.value} alignment="left" spacing={0} style={{ margin: '4px 0' }}>
					<Icon icon={status.icon} size={24} />
					<span style={{ marginLeft: 8 }}>{status.label}</span>
				</HStack>
			))}
		</div>
	);
}
