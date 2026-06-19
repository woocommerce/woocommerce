export type PmPromotion = {
	id: string;
	promo_id?: string;
	payment_method: string;
	payment_method_title?: string;
	type: 'spotlight' | 'badge';
	title: string;
	description?: string;
	cta_label?: string;
	tc_url?: string;
	tc_label?: string;
	badge_text?: string;
	badge_type?: 'primary' | 'success' | 'light' | 'warning' | 'alert';
	footnote?: string;
	image?: string;
};
