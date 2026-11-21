# Remit — PHP wasn't supposed to do that

The demo app from my talk **“PHP wasn't supposed to do that”** at **SymfonyCon Warsaw 2026**.

One Symfony app, served to the web **and** to native iOS and Android shells, with [Symfony UX Native](https://symfony.com/bundles/ux-native) and [Hotwire Native](https://native.hotwired.dev). Remit is a small expense-report app: a month of expenses from a trip to Warsaw, receipt photos, a form, a few totals.

Talk page, slides and the live web version: **[bludevon.com/symfonycon-warsaw-2026](https://bludevon.com/symfonycon-warsaw-2026)**

## Follow the talk, step by step

The history is linear, one commit per step of the talk. Each step is tagged:

| Tag | What it adds | In the talk |
| --- | --- | --- |
| `step-1-web` | The Symfony web app, nothing native | The starting point |
| `step-2-native-shell` | `symfony/ux-native`, the path configuration, the iOS and Android shells (no bridge components) | Demo I: the same app, on a phone |
| `step-3-ux-is-native` | `ux_is_native()` in the templates: the app drops what the shell already draws | Demo II: one template, two surfaces |
| `step-4-bridge` | Four bridge components (button, menu, form, haptic), web and native halves | Demo III and the quick round |

```bash
git checkout step-2-native-shell                     # the app at a given step
git diff step-2-native-shell step-3-ux-is-native     # exactly what a step changes
git diff step-1-web step-4-bridge --stat -- . ':!native' ':!composer.lock' ':!symfony.lock' ':!config/reference.php'
```

That last one is the point of the talk: from the web app to the native app, **no controller, entity, form type or repository changes**. Only templates, one CSS file, four small Stimulus controllers, one configuration class — and the install itself.

`main` is the final state (`step-4-bridge`).

## What is native, and how much of it

Symfony UX Native detects requests coming from the app, adds the `ux_is_native()` Twig function, serves the path configuration from PHP objects, and scaffolds the Stimulus side of bridge components (`make:native-bridge-controller`).

It ships no native code: a PHP bundle can't ship Swift. The native half is in `native/`, written once:

- **iOS** (`native/ios/`, Swift): 219 lines in total, the shell and four components. `ButtonComponent.swift` is the 36-line file from the talk.
- **Android** (`native/android/`, Kotlin): 247 lines, the same shape. Written by translating the Swift, **not tested on a device**.

The components are adapted from the Hotwire Native demo apps (MIT).

## Run it

Requirements: Docker Desktop, `make`. For the iPhone: Xcode 16+ and [XcodeGen](https://github.com/yonaskolb/XcodeGen) (`brew install xcodegen`).

```bash
make init      # image, Symfony app, demo data, local domain and trusted HTTPS (asks for your password)
make up        # start · make down to stop
make urls      # which URL to open where
```

The app runs in a single FrankenPHP container (PHP 8.4+), with SQLite in `var/`. `make db-reset` brings the demo data back. `make` lists every command.

**iOS**

```bash
REMIT_TEAM_ID=XXXXXXXXXX make ios    # generates and opens the Xcode project; your Apple team ID to run on a real iPhone
```

A free Apple ID is enough to run it on your own iPhone. The shell reaches the Mac by its Bonjour name, over plain HTTP on the local network (the Simulator uses `localhost`).

**Android**

```bash
make android            # opens Android Studio
make android-install    # installs and launches on the running emulator (http://10.0.2.2)
```

## Stack

Symfony 8.1 · PHP 8.4+ · Doctrine ORM + SQLite · AssetMapper + Stimulus + Turbo · Symfony UX Native 3.7 · Hotwire Native 1.3 (iOS and Android) · FrankenPHP

---

Made by Félix ([Bludevon](https://bludevon.com)), with the team at [Bakslash](https://baksla.sh).
