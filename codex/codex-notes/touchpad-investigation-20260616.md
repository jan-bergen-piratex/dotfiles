# Touchpad Investigation 2026-06-16

Parent topic: laptop touchpad stopped working.
Current subtopic: distinguish X/libinput issue from I2C/HID/firmware failure.

Evidence:
- Laptop: Lenovo IdeaPad S340-14API, product 81NB.
- Device: `SYNA3255:00 06CB:7F28 Touchpad`, X id 11, `/dev/input/event6`.
- X/libinput reports device enabled and send-events enabled.
- `xinput test` and `xinput test-xi2` captured zero events while Jan moved/clicked.
- Xorg log has repeated `kernel bug: Touch jump detected and discarded` for event6.
- HID child reset via `/tmp/reset-touchpad-hid.fish` completed and reattached device, but touchpad still did not work.
- I2C parent reset via `/tmp/reset-touchpad-i2c.fish` completed and reattached device as `0018:06CB:7F28.0002`, but touchpad still did not work.
- Raw root capture via `/tmp/capture-touchpad-raw.fish` saw zero bytes from `/dev/input/event6` while Jan moved/clicked the touchpad.

Decisions:
- Restore `libinput Disable While Typing Enabled` to original `1` after test.
- Next reset layer: `i2c_hid_acpi` parent unbind/rebind using `/tmp/reset-touchpad-i2c.fish`.

Open questions:
- Did Fn/F6 firmware toggle disable the pad at EC level despite OS seeing it?
- If Fn+F6 fails, try full power drain or BIOS touchpad toggle before assuming hardware fault.
