import { Modal } from '@wordpress/components';

export type SchedulePublishModalProps = Omit<
	Modal.Props,
	'children' | 'title' | 'onRequestClose'
> & {
	title?: string;
	description?: string;
	value?: string;
	onCancel?(): void;
	onSchedule?( value: string ): void;
};
