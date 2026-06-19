/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import type {
	WooPaymentsOverviewDispute,
	WooPaymentsOverviewShell,
	WooPaymentsOverviewTask,
	WooPaymentsOverviewTasksVisibility,
} from '../types';
import {
	formatWooPaymentsAmount,
	getSettingsPaymentsProviderRouteUrl,
} from '../utils';

const DAY_IN_MS = 24 * 60 * 60 * 1000;
const REQUIREMENT_CODE_BLACKLIST = [ 'invalid_value_other' ];

const REQUIREMENT_MESSAGES: Record< string, string > = {
	verification_document_missing_front: __(
		'The uploaded file was missing the front of the document. Upload a complete scan of the document.',
		'woocommerce'
	),
	verification_document_missing_back: __(
		'The uploaded file was missing the back of the document. Upload a complete scan of the document.',
		'woocommerce'
	),
	invalid_tos_acceptance: __(
		'The account needs to accept the terms of service again.',
		'woocommerce'
	),
};

export const formatTaskCurrency = ( amount: number, currency?: string ) =>
	formatWooPaymentsAmount( amount, currency );

const normalizeTimestamp = ( value?: number | string | null ) => {
	if ( value === undefined || value === null || value === '' ) {
		return null;
	}

	if ( typeof value === 'number' ) {
		return value < 10000000000 ? value * 1000 : value;
	}

	const numericValue = Number( value );
	if ( Number.isFinite( numericValue ) ) {
		return numericValue < 10000000000 ? numericValue * 1000 : numericValue;
	}

	const parsedValue = new Date( value ).getTime();

	return Number.isNaN( parsedValue ) ? null : parsedValue;
};

const formatTaskDate = ( value?: number | string | null ) => {
	const timestamp = normalizeTimestamp( value );

	if ( timestamp === null ) {
		return '';
	}

	return new Date( timestamp ).toLocaleDateString( undefined, {
		year: 'numeric',
		month: 'short',
		day: 'numeric',
	} );
};

const getDisputeDueTimestamp = ( dispute: WooPaymentsOverviewDispute ) =>
	normalizeTimestamp(
		dispute.evidence_due_by ??
			dispute.evidence_details?.due_by ??
			dispute.due_by
	);

const getDisputeId = ( dispute: WooPaymentsOverviewDispute ) =>
	dispute.dispute_id || dispute.id || '';

const getDisputeChargeId = ( dispute: WooPaymentsOverviewDispute ) => {
	if ( typeof dispute.charge === 'string' ) {
		return dispute.charge;
	}

	return dispute.charge_id || dispute.charge?.id || dispute.id || '';
};

const getRequirementMessages = ( shell: WooPaymentsOverviewShell ) => {
	const errors = shell.account_status.requirements?.errors ?? [];
	const messages = errors
		.filter(
			( error ) =>
				! error.code ||
				! REQUIREMENT_CODE_BLACKLIST.includes( error.code )
		)
		.map(
			( error ) =>
				( error.code && REQUIREMENT_MESSAGES[ error.code ] ) ||
				error.reason ||
				error.code ||
				''
		);

	return Array.from( new Set( messages.filter( Boolean ) ) );
};

const buildUpdateBusinessDetailsTask = ( {
	shell,
	onOpenUpdateBusinessDetails,
}: {
	shell: WooPaymentsOverviewShell;
	onOpenUpdateBusinessDetails: ( shell: WooPaymentsOverviewShell ) => void;
} ): WooPaymentsOverviewTask | null => {
	if ( ! shell.show_update_details_task ) {
		return null;
	}

	const accountStatus = shell.account_status;
	const status = accountStatus.status;
	const completed = status === 'complete' || status === 'enabled';

	if ( ! accountStatus.details_submitted ) {
		return {
			key: 'complete-setup',
			level: 1,
			title: sprintf(
				/* translators: %s: Payment provider name. */
				__( 'Finish setting up %s', 'woocommerce' ),
				'WooPayments'
			),
			content: __(
				'Complete your business details so you can accept payments and receive payouts.',
				'woocommerce'
			),
			actionLabel: __( 'Finish setup', 'woocommerce' ),
			href: getSettingsPaymentsProviderRouteUrl(
				'/woopayments/onboarding?source=wcpay-finish-setup-task&from=WCPAY_OVERVIEW'
			),
			completed,
			showActionButton: true,
			isDismissable: true,
			allowSnooze: true,
		};
	}

	const messages = getRequirementMessages( shell );
	const hasMultipleMessages = messages.length > 1;
	const hasSingleMessage = messages.length === 1;
	const updateBy = accountStatus.current_deadline
		? sprintf(
				/* translators: %s: Account requirement deadline. */
				__(
					'Update by %s to avoid a disruption in payouts.',
					'woocommerce'
				),
				formatTaskDate( accountStatus.current_deadline )
		  )
		: __(
				'Update your business details to keep payments and payouts working.',
				'woocommerce'
		  );
	let content = [ hasSingleMessage ? messages[ 0 ] : '', updateBy ]
		.filter( Boolean )
		.join( ' ' );

	if ( accountStatus.past_due ) {
		content = hasSingleMessage
			? messages[ 0 ]
			: __(
					'Payments and payouts are disabled for this account until missing business information is updated.',
					'woocommerce'
			  );
	}

	let actionLabel = __( 'Update', 'woocommerce' );
	if ( hasMultipleMessages ) {
		actionLabel = __( 'More details', 'woocommerce' );
	} else if ( completed ) {
		actionLabel = __( 'View details', 'woocommerce' );
	}

	return {
		key: 'update-business-details',
		level: 1,
		title: sprintf(
			/* translators: %s: Payment provider name. */
			__( 'Update %s business details', 'woocommerce' ),
			'WooPayments'
		),
		content,
		actionLabel,
		onClick: () => onOpenUpdateBusinessDetails( shell ),
		completed,
		showActionButton: true,
		isDismissable: true,
		allowSnooze: true,
	};
};

const buildReconnectTask = (
	wpcomReconnectUrl: string
): WooPaymentsOverviewTask | null => {
	if ( ! wpcomReconnectUrl ) {
		return null;
	}

	return {
		key: 'reconnect-wpcom-user',
		level: 1,
		title: sprintf(
			/* translators: %s: Payment provider name. */
			__( 'Reconnect %s', 'woocommerce' ),
			'WooPayments'
		),
		content: sprintf(
			/* translators: %s: Payment provider name. */
			__(
				'%s is missing a connected WordPress.com account. Some functionality will be limited without a connected account.',
				'woocommerce'
			),
			'WooPayments'
		),
		actionLabel: __( 'Reconnect', 'woocommerce' ),
		href: addQueryArgs( wpcomReconnectUrl, {
			from: 'WCPAY_OVERVIEW',
			source: 'wcpay-reconnect-wpcom-user-task',
		} ),
		showActionButton: true,
	};
};

export const isDisputeDueWithinDays = (
	dispute: WooPaymentsOverviewDispute,
	days: number,
	now = Date.now()
) => {
	const dueTimestamp = getDisputeDueTimestamp( dispute );

	return dueTimestamp !== null && dueTimestamp <= now + days * DAY_IN_MS;
};

const buildDisputeTask = (
	disputes: WooPaymentsOverviewDispute[]
): WooPaymentsOverviewTask | null => {
	const urgentDisputes = disputes
		.filter( ( dispute ) => isDisputeDueWithinDays( dispute, 7 ) )
		.sort(
			( a, b ) =>
				( getDisputeDueTimestamp( a ) ?? 0 ) -
				( getDisputeDueTimestamp( b ) ?? 0 )
		);

	if ( urgentDisputes.length === 0 ) {
		return null;
	}

	if ( urgentDisputes.length === 1 ) {
		const dispute = urgentDisputes[ 0 ];
		const chargeId = getDisputeChargeId( dispute );

		return {
			key: `dispute-resolution-task-${ getDisputeId( dispute ) }`,
			level: 1,
			title: sprintf(
				/* translators: %s: Disputed amount. */
				__( 'Respond to a dispute for %s', 'woocommerce' ),
				formatTaskCurrency( dispute.amount ?? 0, dispute.currency )
			),
			content: sprintf(
				/* translators: %s: Dispute response deadline. */
				__( 'Respond by %s.', 'woocommerce' ),
				formatTaskDate( getDisputeDueTimestamp( dispute ) )
			),
			actionLabel: __( 'Respond now', 'woocommerce' ),
			href: getSettingsPaymentsProviderRouteUrl(
				`/woopayments/transactions/details?id=${ encodeURIComponent(
					chargeId
				) }`
			),
			showActionButton: true,
		};
	}

	const currencies = Array.from(
		new Set(
			urgentDisputes
				.map( ( dispute ) => dispute.currency?.toLowerCase() )
				.filter( Boolean )
		)
	);
	const title =
		currencies.length === 1
			? sprintf(
					/* translators: 1: Number of disputes, 2: Total disputed amount. */
					__(
						'Respond to %1$d active disputes for a total of %2$s',
						'woocommerce'
					),
					urgentDisputes.length,
					formatTaskCurrency(
						urgentDisputes.reduce(
							( total, dispute ) =>
								total + ( dispute.amount ?? 0 ),
							0
						),
						currencies[ 0 ]
					)
			  )
			: sprintf(
					/* translators: %d: Number of disputes. */
					_n(
						'Respond to %d active dispute',
						'Respond to %d active disputes',
						urgentDisputes.length,
						'woocommerce'
					),
					urgentDisputes.length
			  );

	return {
		key: `dispute-resolution-task-${ urgentDisputes
			.map( getDisputeId )
			.join( '-' ) }`,
		level: 1,
		title,
		content: sprintf(
			/* translators: %d: Number of disputes due soon. */
			_n(
				'Last week to respond to %d dispute.',
				'Last week to respond to %d disputes.',
				urgentDisputes.length,
				'woocommerce'
			),
			urgentDisputes.length
		),
		actionLabel: __( 'See disputes', 'woocommerce' ),
		href: getSettingsPaymentsProviderRouteUrl(
			'/woopayments/disputes?filter=awaiting_response'
		),
		showActionButton: true,
	};
};

const buildGoLiveTask = ( {
	shell,
	onActivatePayments,
}: {
	shell: WooPaymentsOverviewShell;
	onActivatePayments: () => void;
} ): WooPaymentsOverviewTask | null => {
	if (
		! shell.account.connected ||
		shell.account.live ||
		shell.account.dev_mode ||
		! ( shell.account.test_drive || shell.account.test_mode_onboarding )
	) {
		return null;
	}

	return {
		key: 'go-live-payments',
		level: 3,
		title: __( 'Activate payments', 'woocommerce' ),
		content: __( '10 minutes', 'woocommerce' ),
		onClick: onActivatePayments,
		showActionButton: false,
	};
};

export const buildOverviewTasks = ( {
	shell,
	disputes,
	onOpenUpdateBusinessDetails,
	onActivatePayments,
}: {
	shell: WooPaymentsOverviewShell;
	disputes: WooPaymentsOverviewDispute[];
	onOpenUpdateBusinessDetails: ( shell: WooPaymentsOverviewShell ) => void;
	onActivatePayments: () => void;
} ): WooPaymentsOverviewTask[] =>
	[
		buildUpdateBusinessDetailsTask( {
			shell,
			onOpenUpdateBusinessDetails,
		} ),
		buildReconnectTask( shell.wpcom_reconnect_url ),
		buildDisputeTask( disputes ),
		buildGoLiveTask( { shell, onActivatePayments } ),
	].filter( Boolean ) as WooPaymentsOverviewTask[];

export const getVisibleOverviewTasks = (
	tasks: WooPaymentsOverviewTask[],
	visibility: WooPaymentsOverviewTasksVisibility,
	now = Date.now()
) =>
	tasks.filter(
		( task ) =>
			! visibility.deleted_todo_tasks.includes( task.key ) &&
			! visibility.dismissed_todo_tasks.includes( task.key ) &&
			( ! visibility.remind_me_later_todo_tasks[ task.key ] ||
				visibility.remind_me_later_todo_tasks[ task.key ] < now )
	);
