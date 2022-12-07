# Craft Server Check

This script checks if a web server meets the minimum requirements to run a Craft 4 installation.

## Usage

Run the following in a terminal of any [\*nix](https://en.wikipedia.org/wiki/Unix-like) environment (e.g. Linux, MacOS, WSL):

```bash
curl -Lsf https://raw.githubusercontent.com/craftcms/server-check/HEAD/check.sh | bash
```

> **Note**
> You can [review the substance](https://github.com/craftcms/server-check/blob/main/check.sh) of this script before execution.

### Alternatives

#### Web UI

Upload the `server/` folder to your web server’s web root and load `checkit.php` from a browser to get an HTML report.

#### Remote CLI

The same `server/` folder can be uploaded anywhere on your server and used via the command line to get a plain-text report:

```bash
php checkit.php
```

This is equivalent to the default [usage](#usage) instructions, above.

## Shell exit codes

If all requirements are met, the script will return an exit code of `0`.

The script will return an exit code of `1` if:

- Any errors are encountered, or requirements are not met
- An environment variable `CRAFT_STRICT_SERVER_CHECK=1` is set, and any _warnings_ are found:

  ```bash
  CRAFT_STRICT_SERVER_CHECK=1 php server/checkit.php
  ```

This can be especially useful in a CI/CD pipeline, or a `Dockerfile`, where you want the process to fail if the check does not pass:

```Dockerfile
# Dockerfile
FROM php:8.0-fpm
RUN curl -Lsf https://raw.githubusercontent.com/craftcms/server-check/HEAD/check.sh | bash
```

The official [Craft Docker Images](https://github.com/craftcms/docker) run this check when building to be certain all of Craft's requirements are met in any built image.
