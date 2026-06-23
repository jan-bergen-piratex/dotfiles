---
name: session-todo-sync
description: "Use when Jan wants Codex to ask another running Codex/tmux session for current status, evidence, or todo state via the local session bus, then have only the current main session interpret the response and edit notes/todos.md. Use for cross-session todo audits, Mary/GIC/OnDeck status scavenging, or coordinating project sessions without manual copy-paste."
---

# Session Todo Sync

## Rule

Remote sessions are evidence providers only. They must not edit
`/home/jan/pirate/notes/todos.md`, project files, or shared brain files as part
of a todo-sync request.

The current main session owns interpretation and todo edits. If todos need to be
checked off, renamed, merged, or marked `[q]`, use the `todo-curator` skill in
this session after reading the remote report.

## Workflow

1. Confirm the target session.
   - Use `session-bus list`.
   - If the session is not registered, register it only when the tmux target is
     known and Jan has made that target explicit.

2. Write a broad read-only status request.
   - Use `session-bus send-active <session-id> <message...>` by default when the
     target session is ready to receive a normal bus message.
   - Do not use plain `session-bus send` for ready sessions; it is passive and
     only writes an inbox file.
   - The request must say:
     - inspect relevant repos/docs/runtime state broadly, not only the todo
       wording that obviously matches the session name
     - scan the whole todo file for the session's project/domain and adjacent
       aliases, old names, stale rows, duplicates, and hidden subitems
     - report current status, evidence paths, stale/duplicate todos, and
       check-off candidates
     - distinguish broad project readiness from completed subparts
     - do not edit files
     - write the answer to `reply_to`

3. Queue without overriding active prompts.
   - If Jan says the target has prompts running or queued, do not use
     `session-bus send-active` and do not press `Enter` in the target pane.
   - Use `session-bus send-passive <session-id> <message...>` first. This
     creates the inbox file but does not make anything visible in the Codex UI.
   - Then run `session-bus notify <session-id> <inbox-file>`. This places the
     bus notification text into the target Codex text field without submitting.
   - Immediately send Shift+Tab to the registered tmux target:
     `tmux send-keys -t <tmux-target> S-Tab`.
   - Do not send `Enter`. The intended queued state is: visible in the Codex
     text field, not submitted. Jan confirmed this is the working way to queue
     behind existing Codex prompts.
   - If the target session is not registered, register it only when Jan has
     explicitly identified the tmux target or pane. Do not guess based only on
     pane titles.

4. Notify only when safe.
   - Do not infer readiness from `tmux capture-pane`; Codex history, proposed
     prompts, and live input can look the same.
   - Use `session-bus send-active` or `session-bus notify` only when Jan says
     the target session is ready, or the target session has explicitly agreed to
     receive bus messages.
   - Exception: for the queueing process above, `session-bus notify` is allowed
     only together with the immediate Shift+Tab step and only when Jan asked to
     queue rather than submit.

5. Await or collect the response.
   - If the request was submitted to the target session, stay with the sync:
     poll `session-bus outbox <session-id>` periodically until a response file
     appears, then read it.
   - If the request was queued behind other prompts with the Shift+Tab process,
     report the queued inbox path and do not pretend the sync is complete yet.
     When Jan says the queued task has run or the response is done, immediately
     collect the outbox response in this main session.
   - Do not leave a submitted sync at "message sent" unless Jan explicitly asks
     to stop before the response.

6. Collect the response.
   - Use `session-bus outbox <session-id>` or `session-bus collect <session-id>`.
   - Read the response file yourself.
   - Treat missing evidence, uncertain claims, and broad "done" labels as
     unproven until the report gives concrete evidence.

7. Curate todos locally.
   - Use `todo-curator`.
   - Edit `/home/jan/pirate/notes/todos.md` only from this main session.
   - Mark old renamed/merged wording `[q]`.
   - Check off only items backed by explicit evidence or Jan's statement.
   - Keep still-open project work active even if setup or a subpart is done.
   - Expect a broad cleanup, not just one or two edits. If the remote report
     exposes aliases, old duplicates, stale `[a]` markers, or non-concrete
     category rows, retire those too unless they are historical-only rows that
     should remain untouched.

8. Verify.
   - Re-read the changed todo section.
   - Report the response file path and the todo changes made.

## Request Template

Use this shape and adapt the project name:

```text
Read this request read-only. Do not edit files.

We are syncing Jan's todo state for <project/session>. The todo file is
/home/jan/pirate/notes/todos.md. Assume Jan may be behind in checking items off.

Please inspect broadly:
- the relevant repo/docs/runtime state for your project
- the full todo file for todos in your domain, including old names, aliases,
  adjacent project names, nested subitems, duplicate rolled-forward rows, stale
  `[a]` in-progress markers, and non-concrete category rows
- nearby handovers/docs that might prove whether a todo is done, partial, stale,
  renamed, duplicated, or still open

Do not limit the report to the obvious current todo wording. Jan expects this
sync to find a broad set of todo changes when the project has accumulated stale
rows.

Write the status report to the reply_to path in this bus message.

For each relevant todo or area, include:
- status: done / partial / still open / stale / duplicate
- evidence: concrete file paths, commands, deployed runtime facts, or handover
  docs
- recommendation: check off, keep open, rename, merge, or mark [q]

Also include a "Broad cleanup candidates" section listing older aliases,
duplicates, stale markers, category-only rows, and rows that should be merged
into a current canonical todo. If a broad todo is still open but a subpart is
done, name the subpart separately.

Do not modify todos.md or any project files.
```

## Boundaries

- Do not let remote sessions push todo edits back through the bus.
- Do not use the bus to control tmux lifecycle, kill panes, respawn sessions, or
  manage long-running processes.
- Do not paste secrets from captured panes into the report.
- If a remote session cannot verify runtime state, record that limitation and
  keep the related todo open or partial.
