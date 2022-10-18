/**
 * External dependencies
 */
import { TaskType } from '@woocommerce/data';

export type TaskProps = {
	onComplete: () => void;
	query: Record< string, string >;
	task: TaskType;
};

export type ShippingRecommendationProps = {
	activePlugins: string[];
	isJetpackConnected: boolean;
	isResolving: boolean;
};
