search_up() {
    local look=${PWD%/}

    while [[ -n $look ]]; do
        [[ -e $look/$1 ]] && {
            printf '%s\n' "$look"
            return
        }

        look=${look%/*}
    done

    [[ -e /$1 ]] && echo /
}

work_dir=$(search_up turbo.json)

pnpm -C $work_dir exec turbo run --filter=$npm_package_name "$1" -- -- ${@:2}
