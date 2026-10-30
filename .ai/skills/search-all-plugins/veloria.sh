#!/bin/bash
# Search WordPress.org plugins, themes or core through the Veloria MCP server.
# Usage: veloria.sh '<json arguments for search_code>'
# Example: veloria.sh '{"query":"function\\s+is_valid_status\\s*\\(","file_match":"\\.php$","limit":50}'
set -euo pipefail

URL=https://veloria.dev/mcp
ARGS="${1:?Pass the search_code arguments as JSON}"
HEADERS="$(mktemp)"
trap 'rm -f "$HEADERS"' EXIT

call() {
	curl -s -H 'Content-Type: application/json' -H 'Accept: application/json, text/event-stream' "$@"
}

call -D "$HEADERS" "$URL" \
	-d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-03-26","capabilities":{},"clientInfo":{"name":"search-all-plugins","version":"1"}}}' >/dev/null
SID="$(grep -i '^mcp-session-id:' "$HEADERS" | cut -d' ' -f2 | tr -d '\r')"

call -H "Mcp-Session-Id: $SID" "$URL" -d '{"jsonrpc":"2.0","method":"notifications/initialized"}' >/dev/null

call -H "Mcp-Session-Id: $SID" "$URL" \
	-d "$(jq -nc --argjson a "$ARGS" '{jsonrpc:"2.0",id:2,method:"tools/call",params:{name:"search_code",arguments:$a}}')" \
	| sed 's/^data: //' | grep '^{' | jq -r '.result.content[]?.text // .error // .'
