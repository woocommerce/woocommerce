export type PartialEntity = {
	downloadLimit: number | null;
	downloadExpiry: number | null;
};

export type ManageDownloadLimitsModalProps = {
	initialValue: PartialEntity;
	onSubmit( value: PartialEntity ): void;
	onClose(): void;
};
