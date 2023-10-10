export type RemoveConfirmationModalProps = {
	title: string;
	description: React.ReactNode;
	onRemove(): void;
	onCancel?(): void;
};
