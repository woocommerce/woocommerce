export type RemoveConfirmationModalProps = {
	title: string;
	description: React.ReactNode;
	onRemove(): void | Promise< void >;
	onCancel?(): void;
};
