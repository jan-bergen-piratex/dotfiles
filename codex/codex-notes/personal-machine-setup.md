# Personal Machine Setup

This file records Jan's local working setup for Codex sessions.

## Environment

| Area | Choice |
| --- | --- |
| Hostname | blackbeard |
| Laptop | Lenovo IdeaPad S340-14API |
| Operating system | Debian 13 |
| RAM | About 6 GB usable RAM (`free -h` showed 5.7 GiB total on 2026-06-19) |
| Swap | None visible on 2026-06-19 |
| Window manager | i3 |
| Terminal | Alacritty |
| Terminal multiplexer | tmux |
| Interactive shell | fish |
| Editor | Helix |
| Agent/coding tool | Codex CLI |

## Notes For Codex

- This laptop is memory-constrained. Anything built to run locally on it,
  especially personal setup tooling, should be very lightweight by default.
- Avoid always-on heavyweight services, large Node/Next dev servers, broad
  watchers, Docker stacks, or multiple resident agents unless Jan explicitly
  wants that tradeoff.
- Prefer simple CLI tools, tmux orchestration, SQLite/files, short-lived
  processes, and explicit start/stop commands for local personal tooling.
- Cross-session Codex communication uses the lightweight file bus at
  `/home/jan/pirate/notes/session-bus`, operated by
  `/home/jan/pirate/notes/scripts/session-bus.fish` or `session-bus`.
  It is for messages and artifacts only. It must not kill, respawn, or manage
  tmux panes automatically.
- Do not infer whether a Codex pane is ready from `tmux capture-pane` text.
  Rendered history, proposed prompts, and live user input can look the same.
  Session-bus sends are passive by default; use active tmux notification only
  when Jan or the target session has explicitly made the pane available.
- Jan already uses tmux as part of normal work.
- When giving Jan shell commands, prefer fish syntax.
- For Codex's own tool calls, do not wrap simple commands in `fish -lc`.
- Treat tmux as an available local coordination surface before proposing new orchestration tools.
- If future agent-swarm or shared-bus work is discussed, start from this existing setup.
