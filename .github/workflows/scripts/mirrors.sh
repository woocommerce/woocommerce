echo "$BUILD_BASE is folder where the magic happens"

COMMIT_ORIGINAL_AUTHOR="${GITHUB_ACTOR} <${GITHUB_ACTOR}@users.noreply.github.com>"
echo "$COMMIT_ORIGINAL_AUTHOR"
MONOREPO_COMMIT_MESSAGE=$(cd "${SOURCE_DIR:-.}" && git show -s --format=%B $GITHUB_SHA)
echo "$MONOREPO_COMMIT_MESSAGE"
COMMIT_MESSAGE=$( printf "%s\n\nCommitted via a GitHub action: https://github.com/%s/actions/runs/%s\n" "$MONOREPO_COMMIT_MESSAGE" "$GITHUB_REPOSITORY" "$GITHUB_RUN_ID" )
echo "$COMMIT_MESSAGE"

CLONE_DIR="${BUILD_BASE}/woocommerce"
echo "navigation to ${CLONE_DIR}"
cd "${CLONE_DIR}"

# Initialize the directory as a git repo, and set the remote
echo "Initializing git"
git init -b trunk .
git remote -v 
echo "Adding origin: https://github.com/woocommerce/woocommerce-production"
git remote add origin "https://github.com/woocommerce/woocommerce-production"
git config --local http.https://github.com/.extraheader "AUTHORIZATION: basic $(printf "x-access-token:%s" "$TOKEN" | base64)"

FORCE_COMMIT=
if git -c protocol.version=2 fetch --no-tags --prune --progress --no-recurse-submodules --depth=1 origin trunk; then
    git reset --soft FETCH_HEAD
else
    echo "Failed to find a branch to branch from, just creating an empty one."
    FORCE_COMMIT=--allow-empty
fi
git add -Af

if [[ -n "$FORCE_COMMIT" || -n "$(git status --porcelain)" ]]; then
    echo "Committing to woocommerce/woocommerce-production"
    if git commit --quiet $FORCE_COMMIT --author="${COMMIT_ORIGINAL_AUTHOR}" -m "${COMMIT_MESSAGE}" &&
        { [[ -z "$CI" ]] || git push origin "$BRANCH"; } # Only do the actual push from the GitHub Action
    then
        git show --pretty= --src-prefix="a/woocommerce/woocommerce-production/" --dst-prefix="b/woocommerce/woocommerce-production/" >> "$BUILD_BASE/changes.diff"
        echo "https://github.com/woocommerce/woocommerce-production/commit/$(git rev-parse HEAD)"
        echo "Completed woocommerce/woocommerce-production"
    else
        echo "::error::Commit of woocommerce/woocommerce-production failed"
        EXIT=1
    fi
else
    echo "No changes, skipping woocommerce/woocommerce-production"
fi