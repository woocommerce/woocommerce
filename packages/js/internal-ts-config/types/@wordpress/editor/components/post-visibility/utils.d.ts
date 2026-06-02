declare module '@wordpress/editor/build-types/components/post-visibility/utils' {
	export const VISIBILITY_OPTIONS: ({
	    label: import("@wordpress/i18n").TranslatableText<"Public">;
	    value: string;
	    description: import("@wordpress/i18n").TranslatableText<"Visible to everyone.">;
	} | {
	    label: import("@wordpress/i18n").TranslatableText<"Private">;
	    value: string;
	    description: import("@wordpress/i18n").TranslatableText<"Only visible to site admins and editors.">;
	} | {
	    label: import("@wordpress/i18n").TranslatableText<"Password protected">;
	    value: string;
	    description: import("@wordpress/i18n").TranslatableText<"Only visible to those who know the password.">;
	})[];
}
