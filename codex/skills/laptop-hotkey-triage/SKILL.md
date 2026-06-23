---
name: laptop-hotkey-triage
description: "Use when Jan says a laptop hardware feature stopped working, including trackpad/touchpad/mouse pointer/cursor, Wi-Fi/network/Bluetooth/airplane mode, or camera/webcam/video. First suspect an accidental Lenovo Fn hardware toggle: Fn+F6 touchpad lock, Fn+F7 airplane/flight mode, Fn+F8 camera privacy/off."
---

# Laptop Hotkey Triage

## First Response

When Jan says a laptop hardware feature suddenly stopped working, lead with the matching accidental Fn-key toggle before deeper diagnostics.

| Symptom | First Suspect | Tell Jan |
| --- | --- | --- |
| Trackpad, touchpad, mouse pointer, cursor not moving | `Fn+F6` touchpad lock | Press `Fn+F6` once, then test the trackpad. |
| Wi-Fi, Bluetooth, network vanished, offline, flight mode | `Fn+F7` airplane mode | Press `Fn+F7` once, then check network/Bluetooth. |
| Camera, webcam, video input black/missing | `Fn+F8` camera privacy toggle | Press `Fn+F8` once, then test camera again. |

Phrase it directly: "Most likely: accidental Fn-key hardware toggle." Do not insult Jan; keep the useful heuristic without calling him stupid.

## Known Machine

- Laptop: Lenovo IdeaPad S340-14API, product `81NB`.
- Known incident from 2026-06-16: trackpad looked broken, OS saw device, kernel emitted zero touchpad events, and `Fn+F6` restored it.
- Plain `F6`, `F7`, `F8` may be normal function keys depending BIOS hotkey mode; prefer `Fn+F6`, `Fn+F7`, `Fn+F8`.

## Escalation After Hotkey Check

Only after Jan confirms the relevant Fn key did not help:

- Trackpad: inspect X/libinput and raw `/dev/input/event*`.
- Network/Bluetooth: inspect `rfkill`, NetworkManager, and device presence.
- Camera: inspect browser/app permissions, `/dev/video*`, `v4l2-ctl --list-devices` if installed.

## Avoid

- Do not start with driver reinstall, kernel module reload, BIOS advice, or deep Linux diagnostics while the relevant Fn toggle remains untested.
- Do not treat "device present" as proof it is usable; firmware toggles can leave devices visible but blocked.
