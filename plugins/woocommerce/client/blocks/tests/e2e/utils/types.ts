// Compatibility shim: the real module moved to tests/e2e-pw/utils/blocks
// during the QAO-185 e2e merge. The re-export keeps the old import path (used
// by the blocks e2e utils barrel) working. Removed in QAO-407 (#6) with the
// rest of this tree.
export * from '../../../../../tests/e2e-pw/utils/blocks/types';
