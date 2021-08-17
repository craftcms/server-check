# Craft Server Check

This script checks if a web server meets the minimum requirements to run a Craft 3 installation.

## Usage

Run the following in a terminal of any [\*nix](https://en.wikipedia.org/wiki/Unix-like) environment (e.g. Linux, MacOS, WSL).

```bash
curl -Lsf https://raw.githubusercontent.com/craftcms/server-check/HEAD/check.sh | bash
```

Alternatively, you can upload the `server` folder to your web server's public html folder and load `checkit.php` from your browser
or upload `server` anywhere on your server and execute `php checkit.php` from the console to see the results.
