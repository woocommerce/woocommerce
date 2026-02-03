# Memories

## Patterns

## Decisions

### mem-1770128426-e2eb
> Cart batching implementation flaws identified: 1) microtask timing dependency is fragile, 2) no timeout/abort handling for stuck requests, 3) no retry mechanism, 4) limited extender API (StateHandler too minimal), 5) batchAddCartItems bypasses the queue. New Command Queue pattern proposed with: commands as first-class citizens, single-flight execution, AbortController integration, event-driven updates for extenders.
<!-- tags: iapi, cart, batching, architecture | created: 2026-02-03 -->

## Fixes

## Context
