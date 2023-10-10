export type RemoveConfirmationModalProps = {
	title: string;
	description: React.ReactNode;
	onRemove(): Promise< void >;
	onCancel?(): void;
};
