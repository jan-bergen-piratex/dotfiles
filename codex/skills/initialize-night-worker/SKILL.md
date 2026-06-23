---
name: initialize-night-worker
description: "Use when Jan asks to initialize, start, kick off, or run the night worker in tmux for overnight/weekend todo work. Launch the existing night-todo-runner in the tmux night-worker pane with cheap Codex model workers, many low-risk todos, and do not wait for completion."
---

# Initialize Night Worker

Start the existing local night todo runner in tmux. The goal is to spend spare overnight tokens on many low-risk todos without overloading Jan's laptop.

## Procedure

1. Read `/home/jan/.codex/SOUL.md`.
2. Check the runner and pane:
   ```bash
   fish -n /home/jan/pirate/projects/night-todo-runner/night-todos.fish
   tmux list-panes -a -F '#{session_name}:#{window_index}.#{pane_index}\tpane=#{pane_id}\twindow=#{window_name}\tcmd=#{pane_current_command}\tpath=#{pane_current_path}'
   ```
3. Prefer the pane whose window is `night-worker` and path is `/home/jan/pirate/projects/night-todo-runner`.
4. If no such pane exists, create one in an existing tmux server:
   ```bash
   tmux new-window -n night-worker -c /home/jan/pirate/projects/night-todo-runner
   ```
   If no tmux server exists, report blocked instead of starting detached orchestration.
5. Capture the target pane briefly. If a command is already running, report that the worker is already busy and stop.
6. Send this command to the target pane:
   ```fish
   cd /home/jan/pirate/projects/night-todo-runner; ./night-todos.fish --execute --limit 14 --parallel 2 --risk low --model gpt-5.4-mini --timeout-minutes 90
   ```
7. Do not wait for the run to finish. A short capture after sending is enough to verify initialization.

## Guardrails

- Use `gpt-5.4-mini` by default for cheap workers.
- Increase coverage with `--limit`, not with high parallelism. Keep `--parallel 2` on Jan's 6 GB laptop unless he explicitly asks otherwise.
- Keep `--risk low` unless Jan explicitly asks for medium-risk unattended work.
- Do not use sudo, kill panes, respawn panes, or add monitor loops.
- Do not mark todos done from the initializer. Morning review and todo curation happen separately.
