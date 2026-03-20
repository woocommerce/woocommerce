# GraphQL Query AST Caching Analysis

## Context

The GraphQL request processing involves parsing the query string into a `DocumentNode` AST via `Parser::parse()`, validating it, then executing it. Caching the parsed AST avoids re-parsing identical queries on subsequent requests.

The library (`webonyx/graphql-php ^15.31`) already accepts `DocumentNode` directly in `GraphQL::executeQuery()`, so passing a pre-parsed AST skips parsing entirely.

## Key Primitives

- **`DocumentNode::toArray()`** — converts the entire AST to a plain PHP associative array (no objects, no circular references, no closures). Safe for serialization.
- **`AST::fromArray()`** — reconstructs a `DocumentNode` from a plain array. Uses lazy `NodeList` internally, so child nodes are only reconstituted on access.
- **`noLocation` parser option** — when `true`, the parser omits `Location` objects from the AST, producing significantly smaller output. Location data is only needed for source-position error reporting, not for execution.
- **`ServerConfig::setPersistedQueryLoader()`** — built-in hook for persisted queries. Accepts a callable `(string $queryId, OperationParams $operation): (string|DocumentNode)`.

## Option 1: WP Object Cache with `toArray()`/`fromArray()`

Hash the query string, cache the parsed AST as a plain array:

```php
$hash   = hash( 'sha256', $query );
$cached = wp_cache_get( "graphql_ast_{$hash}", 'wc-graphql' );

if ( false !== $cached ) {
    $document = AST::fromArray( $cached );
} else {
    $document = Parser::parse( $query, [ 'noLocation' => true ] );
    wp_cache_set( "graphql_ast_{$hash}", $document->toArray(), 'wc-graphql' );
}

GraphQL::executeQuery( schema: $schema, source: $document, ... );
```

**Pros:** Zero infrastructure beyond existing WP caching; simple implementation.

**Cons:** Requires a persistent object cache plugin (Redis, Memcached) to survive across requests — without one, WP object cache is per-request only and this gains nothing. Has `serialize()`/`unserialize()` overhead on each cache hit.

## Option 2: OPcache File-Based Caching (Fastest)

Write the AST array as a PHP file so OPcache stores compiled bytecode:

```php
$hash      = hash( 'sha256', $query );
$cache_dir = WP_CONTENT_DIR . '/cache/wc-graphql/';
$file      = $cache_dir . $hash . '.php';

if ( file_exists( $file ) ) {
    $document = AST::fromArray( require $file );
} else {
    $document = Parser::parse( $query, [ 'noLocation' => true ] );
    $exported  = var_export( $document->toArray(), true );
    file_put_contents( $file, "<?php return {$exported};" );
}
```

This is what Lighthouse (the most mature PHP GraphQL framework) uses as its primary caching strategy. OPcache stores compiled bytecode, so `require` is essentially a memory read on subsequent requests.

**Pros:** Fastest option — no deserialization at all once OPcache warms up. No external dependency.

**Cons:** Needs writable directory; needs a cache-busting strategy on library upgrades (e.g. include library version in directory path); disk usage grows with unique query count.

## Option 3: Automatic Persisted Queries (APQ)

The Apollo APQ protocol, using the library's built-in `ServerConfig::setPersistedQueryLoader()`:

1. Client sends just `{ extensions: { persistedQuery: { sha256Hash: "abc" } } }` (no query body).
2. Server looks up the hash — returns cached AST or `PersistedQueryNotFound` error.
3. Client retries with hash + full query — server parses, caches, responds.

The loader can combine with either Option 1 or 2 for storage. This also saves network bandwidth since the query string isn't sent after the first request.

**Pros:** Bandwidth savings, natural fit for mobile/app clients, built-in library support via `setPersistedQueryLoader()`.

**Cons:** Requires client cooperation (Apollo Client supports this natively); two round-trips on first request.

## Comparison

| Approach | Parse saved? | Cold hit cost | Warm hit cost | Dependency |
|----------|-------------|---------------|---------------|------------|
| WP Object Cache | Yes | `toArray()` + serialize | unserialize + `fromArray()` | Persistent cache plugin |
| OPcache files | Yes | `toArray()` + file write | `require` (near-zero) | Writable dir + OPcache |
| APQ | Yes + bandwidth | Same as storage backend | Same as storage backend | Client support |

## Recommendation

**Option 2 (OPcache)** is the best default — it's the fastest, has no external dependency, and is proven by Lighthouse. Use **Option 1** as a fallback when file writing isn't available. **Option 3** can be layered on top of either for API clients that support it.

All three approaches rely on the same core primitive: `DocumentNode::toArray()` for caching and `AST::fromArray()` for restoration. The `executeQuery()` method already accepts a `DocumentNode` directly, so no changes to the execution path are needed beyond adding the cache lookup before the call.

## APQ Client Usage Example

The Automatic Persisted Queries protocol has a three-phase flow:

### Phase 1: Hash-only request (cache miss)

```
GET /wc/v4/graphql?extensions={"persistedQuery":{"version":1,"sha256Hash":"abc123..."}}

→ 200 {"errors":[{"message":"PersistedQueryNotFound","extensions":{"code":"PERSISTED_QUERY_NOT_FOUND"}}]}
```

### Phase 2: Registration (hash + query)

```
POST /wc/v4/graphql
{
  "query": "{ products { nodes { id name } } }",
  "extensions": {
    "persistedQuery": {
      "version": 1,
      "sha256Hash": "abc123..."
    }
  }
}

→ 200 {"data":{"products":{"nodes":[...]}}}
```

The server verifies the hash matches the query, parses the AST, caches it, and executes normally.

### Phase 3: Hash-only request (cache hit)

```
GET /wc/v4/graphql?extensions={"persistedQuery":{"version":1,"sha256Hash":"abc123..."}}

→ 200 {"data":{"products":{"nodes":[...]}}}
```

Subsequent requests only send the hash. The server finds the cached AST and executes directly — no parsing, no query string transmitted.

Apollo Client enables APQ by default. For custom clients, compute `sha256` of the query string and include it in the `extensions` parameter as shown above.

## Gotchas

- **Direct `serialize()` of `DocumentNode` is problematic.** Location objects contain `Token` doubly-linked lists and `Source` references that produce extremely bloated serialized output. Always use `toArray()`/`fromArray()` instead.
- **Cache invalidation on library upgrades.** The AST array structure is tied to the library version. Include the library version in cache keys or directory paths.
- **`noLocation` tradeoff.** Disabling locations makes cached ASTs smaller and faster, but error messages will lack source position info. This is acceptable for production; consider keeping locations in development.
