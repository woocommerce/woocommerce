export type DurationManifest = {
	schemaVersion: number;
	sourceRuns?: number[];
	fallbackDurationMs: number;
	files: Record< string, number >;
};

export type DurationShard = {
	index: number;
	durationMs: number;
	files: string[];
};

export type ShardSelection = {
	files: Set< string > | null;
	fallbackReason: string | null;
};

export function assertPlanCoversCorpus(
	shards: Array< { files: string[] } >,
	files: Iterable< string >
): void;

export function assignDurationShards(
	weightedFiles: Array< { file: string; durationMs: number } >,
	shardCount: number
): DurationShard[];

export function collectProjectFiles(
	report: unknown,
	projectName: string
): string[];

export function planDurationShards( options: {
	files: string[];
	manifest: DurationManifest;
	shardCount: number;
} ): DurationShard[];

export function selectShardFiles( options: {
	files: string[];
	manifest: DurationManifest;
	shard: { current: number; total: number };
} ): ShardSelection;

export function validateDurationManifest( manifest: unknown ): void;
