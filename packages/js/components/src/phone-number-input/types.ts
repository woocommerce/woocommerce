export type DataType = Record<
	string,
	{
		alpha2: string;
		code: string;
		priority: number;
		start?: string[];
		lengths?: number[];
	}
>;
