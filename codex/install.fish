#!/usr/bin/env fish

function ok; set_color green; echo "OK: $argv"; set_color normal; end
function warn; set_color yellow; echo "WARN: $argv"; set_color normal; end
function fail; set_color red; echo "FAIL: $argv"; set_color normal; exit 1; end

set -l script_dir (dirname (status --current-filename))
set -l source_dir (realpath "$script_dir")
set -l target_dir "$HOME/.codex"
set -l backup_root "$HOME/.codex-portable-backups"
set -l stamp (date +%Y%m%d-%H%M%S)
set -l backup_dir "$backup_root/$stamp"

test -d "$source_dir"; or fail "missing source dir: $source_dir"
test -f "$source_dir/AGENTS.md"; or fail "missing AGENTS.md"
test -f "$source_dir/SOUL.md"; or fail "missing SOUL.md"
test -f "$source_dir/config.toml"; or fail "missing config.toml"
test -d "$source_dir/skills"; or fail "missing skills/"
test -d "$source_dir/rules"; or fail "missing rules/"
test -d "$source_dir/memories"; or fail "missing memories/"
test -d "$source_dir/codex-notes"; or fail "missing codex-notes/"
command -v cp >/dev/null; or fail "missing cp"
command -v mkdir >/dev/null; or fail "missing mkdir"
command -v rm >/dev/null; or fail "missing rm"

mkdir -p "$target_dir"; or exit 1
mkdir -p "$backup_dir"; or exit 1

for item in AGENTS.md SOUL.md config.toml skills rules memories codex-notes
    if test -e "$target_dir/$item"
        cp -a "$target_dir/$item" "$backup_dir/$item"; or exit 1
    end
end

for item in AGENTS.md SOUL.md config.toml skills rules memories codex-notes
    rm -rf "$target_dir/$item"; or exit 1
    cp -a "$source_dir/$item" "$target_dir/$item"; or exit 1
end

ok "installed portable Codex setup into $target_dir"
ok "backup written to $backup_dir"
warn "auth.json was not copied; authenticate Codex on this device"
