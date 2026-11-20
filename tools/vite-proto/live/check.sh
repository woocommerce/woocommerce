#!/bin/bash
# Load store pages, record console errors, a DOM probe and a screenshot per page.
# Usage: check.sh <label> [page ...]
LABEL=$1; shift
OUT=/home/user/wc-bench/live/$LABEL
mkdir -p "$OUT"
export AGENT_BROWSER_SESSION=wc-vite-proto
BASE=http://localhost:8090
PAGE_ID=7
declare -A URLS=(
  [shop]="$BASE/shop/"
  [product]="$BASE/?p=12"
  [blocks-page]="$BASE/vite-proto-blocks/"
  [cart]="$BASE/cart/"
  [checkout]="$BASE/checkout/"
  [admin-home]="$BASE/wp-admin/admin.php?page=wc-admin"
  [analytics]="$BASE/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Foverview"
  [analytics-revenue]="$BASE/wp-admin/admin.php?page=wc-admin&path=%2Fanalytics%2Frevenue"
  [product-editor]="$BASE/wp-admin/post-new.php?post_type=product"
  [orders]="$BASE/wp-admin/admin.php?page=wc-orders"
  [settings]="$BASE/wp-admin/admin.php?page=wc-settings"
  [payments-settings]="$BASE/wp-admin/admin.php?page=wc-settings&tab=checkout"
  [block-editor]="$BASE/wp-admin/post.php?post=$PAGE_ID&action=edit"
  [cart-editor]="$BASE/wp-admin/post.php?post=7&action=edit"
)
ORDER=(shop product blocks-page cart checkout admin-home analytics analytics-revenue product-editor orders settings payments-settings block-editor cart-editor)
[ $# -gt 0 ] && ORDER=("$@")
PROBE='JSON.stringify({title:document.title.slice(0,60),wc:Object.keys(window.wc||{}).sort().join(","),scripts:document.querySelectorAll("script[src*=\"/assets/client/\"]").length,styles:document.querySelectorAll("link[href*=\"/assets/client/\"]").length,products:document.querySelectorAll(".wc-block-grid__product,.wc-block-product,li.product").length,cartItems:document.querySelectorAll(".wc-block-cart-items__row").length,checkoutFields:document.querySelectorAll(".wc-block-checkout input").length,placeOrder:!!document.querySelector(".wc-block-components-checkout-place-order-button"),price:document.querySelectorAll(".wc-block-components-product-price,.wc-block-components-totals-item__value").length,reviews:document.querySelectorAll(".wc-block-review-list-item__item,.wc-block-components-review-list-item__item").length,loading:document.querySelectorAll(".is-loading").length,adminHeader:!!document.querySelector(".woocommerce-layout__header"),adminCards:document.querySelectorAll(".woocommerce-card,.components-card").length,charts:document.querySelectorAll(".woocommerce-chart,.woocommerce-summary").length,editorBlocks:(document.querySelector("iframe[name=editor-canvas]")?document.querySelector("iframe[name=editor-canvas]").contentDocument:document).querySelectorAll("[data-type^=\"woocommerce/\"]").length,editorWarnings:(document.querySelector("iframe[name=editor-canvas]")?document.querySelector("iframe[name=editor-canvas]").contentDocument:document).querySelectorAll(".block-editor-warning").length,bodyText:document.body.innerText.replace(/\s+/g," ").length})'
agent-browser open "$BASE/wp-login.php" >/dev/null 2>&1
agent-browser set viewport 1280 900 2 >/dev/null 2>&1
if agent-browser eval 'document.querySelector("#user_login") ? "login" : "in"' 2>/dev/null | grep -q login; then
  agent-browser fill '#user_login' admin >/dev/null; agent-browser fill '#user_pass' password >/dev/null; agent-browser click '#wp-submit' >/dev/null; agent-browser wait 2000 >/dev/null
fi
# One item in the cart.
agent-browser open "$BASE/?add-to-cart=11" >/dev/null 2>&1; agent-browser wait 1500 >/dev/null
for name in "${ORDER[@]}"; do
  agent-browser errors --clear >/dev/null 2>&1; agent-browser console --clear >/dev/null 2>&1
  agent-browser open "${URLS[$name]}" >/dev/null 2>&1
  agent-browser wait 6000 >/dev/null 2>&1
  agent-browser eval "$PROBE" > "$OUT/$name.probe.txt" 2>&1
  agent-browser errors > "$OUT/$name.errors.txt" 2>&1
  agent-browser console > "$OUT/$name.console.txt" 2>&1
  agent-browser screenshot "$OUT/$name.png" >/dev/null 2>&1
  echo "$name errors=$(grep -vc '^\s*$' "$OUT/$name.errors.txt") consoleErr=$(grep -ci 'error' "$OUT/$name.console.txt") probe=$(cat "$OUT/$name.probe.txt" | head -c 900)"
done
