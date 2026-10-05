import { vi } from 'vitest';

export const fetchMock = vi.fn().mockResolvedValue( {
	ok: true,
	json: () => Promise.resolve( {} ),
} );

// eslint-disable-next-line @typescript-eslint/no-explicit-any
global.fetch = fetchMock as any;
