---
name: xcc-clipboard
description: "Use only when Jan includes xcc and wants commands or prompts copied to the X clipboard with xclip."
---

# XCC Clipboard

## Trigger

Use only when the latest user prompt contains the lowercase letters `xcc`.

- `xcc` is one-prompt only. Do not keep clipboard-copy behavior active for later prompts.
- Do not trigger from earlier conversation history, file contents, examples, or this skill text.
- If `xcc` is absent from the latest prompt, never touch the clipboard.
- Treat `xcc` as a control marker, not part of the user's task text.

## Job

When `xcc` is present, automatically run `xclip` to put the handoff payload into Jan's clipboard.

The payload is one of:

- command(s) Jan should execute;
- a prompt Jan should paste into another Codex/chat/session;
- another exact text block Jan clearly needs copied.

If there is no concrete payload to copy, do not invent one. Answer normally and say no clipboard payload existed.

## Payload Rules

- Copy only the payload, not Markdown fences, explanations, bullets, or surrounding prose.
- For commands, use Fish syntax when writing commands for Jan.
- Multiple commands are newline-separated in execution order.
- For prompts, copy the full prompt text exactly as intended.
- Do not put secrets, tokens, passwords, private keys, or destructive commands into the clipboard unless Jan explicitly included or approved that exact content in the current prompt.
- If the task also requires explanation, first copy the payload, then briefly state what was copied.

## Xclip Execution

Prefer robust copying over clever quoting.

For a short one-line command with simple quoting, this is acceptable:

```bash
printf '%s\n' '<payload>' | xclip -selection clipboard
```

For multiline prompts, multiline commands, or quote-heavy payloads:

1. Write the payload to a temporary file under `/tmp`, such as `/tmp/codex-xcc-clipboard.txt`.
2. Run:

```bash
xclip -selection clipboard < /tmp/codex-xcc-clipboard.txt
```

3. Optionally remove the temp file after verifying `xclip` exited successfully.

If `xclip` is missing or cannot access the X selection, say that explicitly and include the payload in the response so Jan can copy it manually.

## Verification

After running `xclip`, check the command exit status. Do not use `xclip -o` unless Jan asks; reading clipboard contents can expose unrelated clipboard data if the copy failed.

Final response after a successful copy should be short:

```text
Copied to clipboard: <brief payload type>.
```

Examples: `Copied to clipboard: Codex orchestrator prompt.` or `Copied to clipboard: 3 Fish commands.`
