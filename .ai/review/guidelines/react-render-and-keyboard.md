# React render loops, keys and keyboard

**Source:**

- #63829 (10.6.2): a SlotFill created in render caused repeated re-renders.
- #59062: a11y radios change reverted.
- #64890: focus indicator.
- #60509: delayed rendering reverted.
- #59429.
- Earlier review findings: Enter on pagination or search triggered the modal's Add action; `key={undefined}`.

**What broke:** Components re-rendered in loops or remounted, and keyboard input triggered the wrong action inside forms and modals.

**Pattern to catch:** Creating SlotFills or stores inside render. Unstable or missing keys. Effects without deps. New buttons inside forms or modals without `type="button"` or key handling.

**How to confirm:** Where is the SlotFill or store created? What does Enter do inside the nearest form? Is each key unique and stable across renders?

**Don't flag when:** Static lists that never reorder.
