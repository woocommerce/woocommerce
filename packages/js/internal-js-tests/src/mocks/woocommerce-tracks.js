import { vi } from 'vitest';

export const recordEvent = vi.fn();
export const recordPageView = vi.fn();
export const bumpStat = vi.fn();
export const queueRecordEvent = vi.fn();
