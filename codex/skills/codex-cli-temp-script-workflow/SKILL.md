---
name: codex-cli-temp-script-workflow
description: "Use when command sequences should become safe local scripts with preflights, dry runs, and clear execution steps."
---

# Codex CLI Temp Script Workflow

Use this workflow when user wants command sequences delivered as script files instead of large pasted command blocks.

## Core Rule

When command chain is more than a few lines, create a local `/tmp/*.fish` file immediately. Do not first explain intended script. Do not split "I will create it" and creation into separate answers.

This skill does not forbid proposing commands. Keep normal ability to suggest commands, explain commands, or execute commands when that is better. It only restructures long command blocks that would otherwise be pasted into chat.

Default to local scripts. A script should stay on Jan's machine when it can
safely perform the work from there, including local repo edits, local Git
operations, and SSH calls used only as substeps. Do not copy a script to the VPS
just because a VPS exists or because the Jack script archive exists.

## Hard Transport Rule

For scripts that must run on the VPS, Git is never the delivery mechanism.

The old Jack `/opt/tech-brain` script archive was retired on 2026-06-08. Do not
recreate it as a transport path. Do not use Git commits, Git pushes, or VPS Git
pulls to transfer runnable scripts to Jack.

When a higher-level skill such as `piratex-brain-librarian` asks for one planned
execution, prefer a single local driver Fish script that performs the copy,
remote execution, sync, and verification steps inside one approved command. The
driver script may still copy durable remote helper scripts into the Jack archive;
the point is to avoid many separate approval prompts for one coherent operation.

Required transport for every VPS script:

1. write local `/tmp/name.fish`
2. `chmod +x /tmp/name.fish`
3. `scp` one-off VPS scripts to `/tmp/name.fish`; install active cron helpers directly under `/opt/jack-cron-jobs`
4. run it with the execution tool approval prompt, unless root/sudo interaction
   is required.

This requirement is strict. If a script needs to become a durable source
artifact, commit it from the local source repo separately after the runtime
operation is done.

Use local `tech-brain` Git only for docs/knowledge that Mary should read. Use
VPS `git pull` only to sync docs/knowledge into Mary's readable brain. Do not
use it as script transport.

## Output Rule

After creating file, say at most one line with local path and target location:

```text
Created: /tmp/name.fish; target: local
```

or:

```text
Created: /tmp/name.fish; copied to VPS:/tmp/name.fish
```

Add one short caution only if script is destructive, exposes a service, edits auth, or changes firewall/SSH.

## Script Standards

- Always write Fish scripts.
- Start Fish scripts with:

```fish
#!/usr/bin/env fish
```

- Use `; or exit 1` after important commands.
- Use clear `cd` to expected working dir.
- End scripts with a concise result summary: what succeeded, what failed, and
  whether the script completed. Do not append a tutorial or long next-step
  instructions.
- Use Fish colors for status output: green for OK, yellow for WARN, blue for
  HINT, red for FAIL. Prefer helper functions:

```fish
function ok; set_color green; echo "OK: $argv"; set_color normal; end
function warn; set_color yellow; echo "WARN: $argv"; set_color normal; end
function hint; set_color blue; echo "HINT: $argv"; set_color normal; end
function fail; set_color red; echo "FAIL: $argv"; set_color normal; exit 1; end
```

- Use `WARN` only for a real warning/risk/problem.
- Use `HINT` for helpful context, optional next checks, or non-blocking notes.
- Keep output concise; status should be scannable, not verbose logs.
- Keep scripts idempotent when possible.
- Do not default to Python or other helper languages for text/documentation edits.
- For `piratex-brain-librarian` or Mary documentation scripts, use Fish plus basic Unix commands only: `test`, `mkdir`, `cp`, `mv`, `rm` when explicitly safe, `grep`, `sed`, `awk`, `printf`, `cmp`, `install`, `wc`, `find`, and similar standard tools.
- For documentation scripts, store generated or replacement contents in Fish variables before writing them. Prefer:

```fish
set -l target /path/to/file.md
set -l content '# Heading

Body text.'
printf '%s\n' "$content" > "$target.tmp"; or exit 1
cmp -s "$target.tmp" "$target"
or install -m 0644 "$target.tmp" "$target"; or exit 1
```

- Use direct `sed`, `awk`, `mv`, `cp`, and `install` steps for moves and small mechanical edits. Keep each mutation visible in the script.
- Use Python/Perl/Node/Ruby only when the task truly requires a structured parser that basic Unix tools cannot handle, and state why in the script comments. Do not use them for ordinary Markdown/doc curation.
- Do not use Bash heredoc syntax (`<<`) in scripts for this user. For documentation changes, generate target files from Fish variables with `printf`.
- Avoid fragile long one-liners.
- Avoid secrets in script files unless user explicitly provides throwaway/dev values.
- After creating local script, run `chmod +x /tmp/name.fish`.
- If script should execute on VPS, copy one-off scripts to `/tmp` with `scp`.
  Install scripts under `/opt/jack-cron-jobs` only when they are active host cron
  helpers:

```fish
scp -F /dev/null /tmp/name.fish redbeard@37.120.166.157:/tmp/name.fish
```

- Also provide the corresponding execution command unless the script should not
  be executed immediately:

```fish
ssh -F /dev/null redbeard@37.120.166.157 'fish /tmp/name.fish'
```

- If the script can run non-interactively, use the execution tool with
  `sandbox_permissions="require_escalated"` so the UI shows allow/deny. Do not
  merely paste the SSH command.
- If the script requires interactive root/sudo password input, do not use the
  execution tool for the run. Provide the SSH command as text and explicitly say
  it requires sudo/root interaction in the user's terminal.
- In final/output line, include both paths/commands when useful and keep it concise.
- Do not recreate `/opt/tech-brain/ops/jack/tmp-fish-scripts` on Jack. That
  host checkout was retired on 2026-06-08.

## Preflight Assertions

Every script must begin with state checks before mutation:

- Assert expected user/host/workdir when relevant:

```fish
test (whoami) = redbeard; or begin
    echo "Wrong user: expected redbeard"
    exit 1
end
```

- Assert required dirs/files/binaries exist before use:

```fish
test -d /opt/hermes-agent/deployment; or exit 1
test -f /opt/hermes-agent/deployment/.env; or exit 1
command -v docker >/dev/null; or exit 1
```

- Assert target path is absent, empty, or intentionally backed up before writing:

```fish
if test -e "$target"
    set backup "$target.backup."(date +%Y%m%d-%H%M%S)
    mv "$target" "$backup"; or exit 1
end
```

- Assert Docker/systemd resources will not collide before creating them:

```fish
docker ps -a --format '{{.Names}}' | grep -qx my-service
and begin
    echo "Container already exists: my-service"
    exit 1
end
```

- For destructive cleanup, make backup first or explicitly narrow deletion to known paths.
- For generated config, validate after writing before restart:

```fish
docker compose config >/tmp/compose-check.txt; or exit 1
```

- If an assumption cannot be validated inside the script, print the missing assumption and exit nonzero instead of proceeding.

## Naming

Use descriptive names:

```text
/tmp/install-docker-debian.fish
/tmp/write-hermes-env.fish
/tmp/fix-onecli-db.fish
/tmp/lock-onecli-localhost.fish
```

## When To Explain More

Explain before or after only when:

- user asks what command does
- change is security-sensitive
- command may break SSH access
- command deletes data
- user shows an error

Even then, keep explanation short and create/update script in same turn.
