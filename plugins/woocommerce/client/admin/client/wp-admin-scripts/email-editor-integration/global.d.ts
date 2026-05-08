declare module '*.json' {
	const value: any;
	export default value;
}

interface Window {
	WooCommerceEmailEditor: {
		current_post_type: string;
		current_post_id: string;
		email_types: {
			value: string;
			label: string;
			id: string;
		}[];
		sender_settings: {
			from_name: string;
			from_address: string;
		};
	};
}

/**
 * Shape of the `woocommerce_data` field on `woo_email` posts.
 *
 * All string fields are nullable because the REST schema in
 * `src/Internal/EmailEditor/EmailApiController.php` declares them with
 * `Builder::string()->nullable()` and the PHP layer uses `null` as the
 * "unset" sentinel.
 */
interface EmailWooCommerceData {
	recipient: string | null;
	cc: string | null;
	bcc: string | null;
	preheader: string | null;
	email_type: string | null;
	subject: string | null;
	subject_full: string | null;
	subject_partial: string | null;
	default_subject: string | null;
	is_manual: boolean;
	enabled: boolean;
}

/**
 * Shape of the `woocommerce_data` field on `wp_template` records used by the
 * email editor. Templates carry sender options rather than per-email form
 * fields.
 */
interface TemplateWooCommerceData {
	sender_settings?: {
		from_address?: string;
		from_name?: string;
	};
}

