from: current Codex session / Jan-assisted
to: incoming Codex session on Jan's new device
date: 2026-06-23
time: 11:59 Europe/Berlin

# MacBook Linux Bring-Up Handoff

## System

This session is about Jan bringing up a bare Fedora install on an Apple Silicon
M1 MacBook from a TTY. The immediate goal is to get networking working, then
install Xorg and i3.

## Verified State

- Jan is on a TTY on the Fedora M1 MacBook.
- The install is bare and has no window manager.
- Jan said: "i have fedora installed on my M1 macbook. its bare, no WM. i have basics set up and i am in a tty. i want to install x and i3"
- Jan then said: "i dont have uplink. how configure network?"
- No direct access to the MacBook system state was available from this Codex session.
- No repo/project files are involved.
- Local machine setup note on the current old device says Jan normally uses i3, Alacritty, tmux, fish, Helix, and Codex CLI:
  `/home/jan/.codex/codex-notes/personal-machine-setup.md`
- This handoff request came through the session bus inbox:
  `/home/jan/pirate/notes/session-bus/sessions/macbook-linux/inbox/2026-06-23T10-00-40Z_macbook-linux.md`

## Guidance Already Given

### Install Xorg and i3 after network is available

Suggested commands for Fedora:

```fish
sudo dnf upgrade --refresh

sudo dnf install \
  xorg-x11-server-Xorg \
  xorg-x11-xinit \
  xorg-x11-drv-libinput \
  xorg-x11-xauth \
  dbus-x11 \
  i3 \
  i3status \
  i3lock \
  dmenu \
  alacritty \
  xterm
```

Create X startup file:

```fish
printf '%s\n' 'exec dbus-run-session i3' > ~/.xinitrc
```

Start i3:

```fish
startx
```

Optional display manager after `startx` is proven:

```fish
sudo dnf install lightdm lightdm-gtk
sudo systemctl enable lightdm
sudo systemctl set-default graphical.target
sudo reboot
```

Assumption attached to the advice: this is Fedora Asahi Remix or another
Asahi-based Fedora install suitable for an M1 MacBook.

### Network triage commands already suggested

Check NetworkManager:

```fish
systemctl status NetworkManager
sudo systemctl enable --now NetworkManager
nmcli device
```

Wi-Fi connect path:

```fish
nmcli radio wifi on
nmcli device wifi list
sudo nmcli device wifi connect "YOUR_WIFI_NAME" password "YOUR_WIFI_PASSWORD"
ping -c 3 fedoraproject.org
```

Hidden SSID:

```fish
sudo nmcli device wifi connect "YOUR_WIFI_NAME" password "YOUR_WIFI_PASSWORD" hidden yes
```

Fallback UI:

```fish
nmtui
```

If NetworkManager tools are missing but iwd exists:

```fish
systemctl status iwd
sudo systemctl enable --now iwd
iwctl
```

Inside `iwctl`:

```text
device list
station wlan0 scan
station wlan0 get-networks
station wlan0 connect YOUR_WIFI_NAME
exit
```

Replace `wlan0` with the real device from `device list`.

Phone tether path:

```fish
nmcli device
sudo dhclient
```

Jan was asked to paste these outputs if stuck:

```fish
nmcli device
ip link
systemctl status NetworkManager --no-pager
```

## Assumptions

- The new MacBook install has Fedora userspace and `systemd`.
- NetworkManager may already be installed on the bare install, but this is not
  verified.
- Wi-Fi firmware and Asahi kernel support may already be present if this is
  Fedora Asahi Remix. If this is plain Fedora installed in an unusual way, Wi-Fi
  may need Asahi-specific packages or a reinstall via the supported Asahi path.
- Jan's preferred shell for instructions is fish, but he may be in bash on the
  new bare install. The commands above are POSIX-compatible enough except for
  fish convenience expectations; avoid fish-only syntax unless confirmed.

## Open Questions

- Is this Fedora Asahi Remix, or plain Fedora on Apple Silicon?
- Does `nmcli` exist?
- Does `NetworkManager` exist and run?
- What does `ip link` show for network interfaces?
- Is Jan trying to use Wi-Fi, USB tethering, or Ethernet via dongle?
- If Wi-Fi scan shows no networks, does `dmesg` report firmware or brcmfmac
  errors?

## Next Session Should Read First

1. This handoff.
2. The bus inbox that requested it:
   `/home/jan/pirate/notes/session-bus/sessions/macbook-linux/inbox/2026-06-23T10-00-40Z_macbook-linux.md`
3. Jan's local setup note for preference context:
   `/home/jan/.codex/codex-notes/personal-machine-setup.md`

## Next Actions

- Ask Jan for the exact output of:

```fish
nmcli device
ip link
systemctl status NetworkManager --no-pager
```

- If `nmcli` is present, walk Jan through Wi-Fi connect or USB tether.
- If NetworkManager is absent and there is no network, use whatever offline
  packages are already present or get temporary uplink via USB tether/Ethernet
  before installing X/i3.
- Once network works, install Xorg/i3 using `dnf`, then verify with `startx`.

## Blockers

- The current session cannot inspect the M1 MacBook directly.
- Networking cannot be completed without Jan either running commands on the TTY
  or providing their output.

## Do Not Change Or Re-Litigate Without Jan

- Do not edit `/home/jan/pirate/notes/todos.md` for this request.
- Do not perform unrelated repo cleanup, deploys, pushes, or code changes.
- Do not assume a display manager is needed before proving `startx` works.
- Do not include secret Wi-Fi passwords in handoffs or bus messages.
