# Public PHP API backward compatibility

**Source:**

- #64394 added `get_entry_count()` to `FeedInterface`; older Stripe Gateway implementations fatalled and #65965 (10.9.1) removed it.
- #50685: `global_unique_id` removed from an interface for the same reason.
- #65713: restored REST controller `get_images()` signatures.
- #66550: restored a removed gallery helper.
- #62789: re-added the deprecated PayPal class require.
- #64461.
- Earlier review findings: a `final` `@internal` method gained a required param; public methods renamed on a container-resolvable class; a public return changed from null.

**What broke:** Extensions implement WC interfaces, extend its classes, and call `@internal` code. Adding a required method or changing a signature fatalled them on update, and core had to restore the old shape.

**Pattern to catch:** - A required method added to an interface or abstract class.

- A public or protected signature, default, or return type changed.
- A public class, method, function, or constant removed or renamed without a deprecated wrapper.
The `Internal` namespace and `@internal` are not exempt.

**How to confirm:** Grep in-tree implementors and callers, and treat an empty result as "no in-tree consumer", not "safe". Check for a `wc_deprecated_function` wrapper or a default implementation in an abstract base.

**Don't flag when:** New optional params with defaults, at the end. New methods on a concrete class only. Removal of a symbol deprecated two or more majors ago that keeps a stub.
