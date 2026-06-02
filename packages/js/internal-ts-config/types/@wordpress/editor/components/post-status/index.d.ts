declare module '@wordpress/editor/build-types/components/post-status' {
	export default function PostStatus(): import("react").JSX.Element | null;
	export const STATUS_OPTIONS: ({
	    label: import("@wordpress/i18n").TranslatableText<"Draft">;
	    value: string;
	    description: import("@wordpress/i18n").TranslatableText<"Not ready to publish.">;
	} | {
	    label: import("@wordpress/i18n").TranslatableText<"Pending">;
	    value: string;
	    description: import("@wordpress/i18n").TranslatableText<"Waiting for review before publishing.">;
	} | {
	    label: import("@wordpress/i18n").TranslatableText<"Private">;
	    value: string;
	    description: import("@wordpress/i18n").TranslatableText<"Only visible to site admins and editors.">;
	} | {
	    label: import("@wordpress/i18n").TranslatableText<"Scheduled">;
	    value: string;
	    description: import("@wordpress/i18n").TranslatableText<"Publish automatically on a chosen date.">;
	} | {
	    label: import("@wordpress/i18n").TranslatableText<"Published">;
	    value: string;
	    description: import("@wordpress/i18n").TranslatableText<"Visible to everyone.">;
	})[];
}
