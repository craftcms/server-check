# Craft Server Check

This script checks if a web server meets the minimum requirements to run a Craft 3 installation.

## Usage

Run the following in a terminal of any [\*nix](https://en.wikipedia.org/wiki/Unix-like) environment (e.g. Linux, MacOS, WSL).

```bash
curl -Lsf https://raw.githubusercontent.com/craftcms/server-check/HEAD/check.sh | bash
```

Alternatively, you can upload the `server` folder to your web server's public html folder and load `checkit.php` from your browser
or upload `server` anywhere on your server and execute `php checkit.php` from the console to see the results.

## Shell exit codes

If all requirements are met, the script will return an exit code of `0`.

The script will return an exit code of `1` if:

- Any errors are encountered, or requirements are not met
- An environment variable `CRAFT_STRICT_SERVER_CHECK=1` is set, and any _warnings_ are found:

  ```bash
  CRAFT_STRICT_SERVER_CHECK=1 php server/checkit.php
  ```

This can be espically useful in a CI/CD pipeline, or a `Dockerfile`, where you want the process to fail if the check does not pass:

```Dockerfile
# Dockerfile
FROM php:8.0-fpm
RUN curl -Lsf https://raw.githubusercontent.com/craftcms/server-check/HEAD/check.sh | bash
```

The official [Craft Docker Images](https://github.com/craftcms/docker) run this check when building to be certain all of Craft's requirements are met in any built image.
