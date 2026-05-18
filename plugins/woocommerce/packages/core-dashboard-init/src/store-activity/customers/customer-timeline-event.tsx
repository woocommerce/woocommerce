import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Link } from '@wordpress/ui';
import type { CustomerRecord } from '../../data';

type CustomerTimelineEventProps = {
	customer: CustomerRecord;
};

function formatCustomerName( customer: CustomerRecord ): string {
	const first = customer.first_name?.trim() ?? '';
	const last = customer.last_name?.trim() ?? '';
	const fullName = `${ first } ${ last }`.trim();
	return fullName || customer.username || customer.email;
}

/**
 * Renders the content of a single Customer activity event in the Store
 * Activity timeline. Links to the WP-admin user edit screen for that
 * customer.
 */
export function CustomerTimelineEvent( {
	customer,
}: CustomerTimelineEventProps ) {
	const displayName = formatCustomerName( customer );
	const href = `/wp-admin/user-edit.php?user_id=${ customer.id }`;

	return (
		<span>
			{ createInterpolateElement(
				__( '<customerLink /> registered', 'woocommerce' ),
				{
					customerLink: <Link href={ href }>{ displayName }</Link>,
				}
			) }
		</span>
	);
}
