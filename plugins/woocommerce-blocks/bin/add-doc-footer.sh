#!/bin/bash
function update_footer {
	export REPLACEWITH='

---

[We'\''re hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20'$1')

'

	# Replace everything after <!-- FEEDBACK -->
	if grep -q "$STARTTAG" "$1"; then
		awk '/<!-- FEEDBACK -->/ {exit} {print}' $1 > tmp && mv tmp $1
	fi

	# Append feedback section.
	printf '%s\n' "<!-- FEEDBACK -->$REPLACEWITH<!-- /FEEDBACK -->" '' >> $1
}

find ./docs -name "*.md" ! -path "./docs/examples*"  ! -path "./docs/internal-developers*"|while read filename; do
  update_footer $filename
done

find ./packages/checkout -name "*.md"|while read filename; do
  update_footer $filename
done

find ./src/StoreApi -name "*.md"|while read filename; do
  update_footer $filename
done
