<?php
/**
 * These are the default Craft requirements for [RequirementsChecker]] to use.
 */

/** @var RequirementsChecker $this */
$requirements = array(
    array(
        'name' => 'PHP 8.0.2+',
        'mandatory' => true,
        'condition' => PHP_VERSION_ID >= 80002,
        'memo' => 'PHP 8.0.2 or later is required.',
    ),
);

$conn = $this->getDbConnection();
$pdoExtensionRequirement = null;

switch ($this->dbDriver) {
    case 'mysql':
        $pdoExtensionRequirement = array(
            'name' => 'PDO MySQL extension',
            'mandatory' => true,
            'condition' => extension_loaded('pdo_mysql'),
            'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/ref.pdo-mysql.php">PDO MySQL</a> extension is required.'
        );
        if ($conn !== false) {
            $version = $conn->getAttribute(PDO::ATTR_SERVER_VERSION);
            if (preg_match('/[\d.]+-([\d.]+)-\bMariaDB\b/', $version, $match)) {
                $name = 'MariaDB';
                $version = $match[1];
                $requiredVersion = $this->requiredMariaDbVersion;
                $tzUrl = 'https://mariadb.com/kb/en/time-zones/#mysql-time-zone-tables';
            } else {
                $name = 'MySQL';
                $requiredVersion = $this->requiredMySqlVersion;
                $tzUrl = 'https://dev.mysql.com/doc/refman/5.7/en/time-zone-support.html';
            }
            $requirements[] = array(
                'name' => "{$name} {$requiredVersion}+",
                'mandatory' => true,
                'condition' => version_compare($version, $requiredVersion, '>='),
                'memo' => "{$name} {$requiredVersion} or higher is required to run Craft CMS.",
            );
            $requirements[] = array(
                'name' => "{$name} InnoDB support",
                'mandatory' => true,
                'condition' => $this->isInnoDbSupported($conn),
                'memo' => "Craft CMS requires the {$name} InnoDB storage engine to run.",
            );
            $requirements[] = array(
                'name' => "{$name} timezone support",
                'mandatory' => false,
                'condition' => $this->validateDatabaseTimezoneSupport($conn),
                'memo' => "{$name} should be configured with <a rel='noopener' target='_blank' href='{$tzUrl}'>full timezone support</a>.",
            );
        }
        break;
    case 'pgsql':
        $pdoExtensionRequirement = array(
            'name' => 'PDO PostgreSQL extension',
            'mandatory' => true,
            'condition' => extension_loaded('pdo_pgsql'),
            'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/ref.pdo-pgsql.php">PDO PostgreSQL</a> extension is required.'
        );
        if ($conn !== false) {
            $requirements[] = array(
                'name' => "PostgreSQL {$this->requiredPgSqlVersion}+",
                'mandatory' => true,
                'condition' => $this->checkDatabaseServerVersion($conn, $this->requiredPgSqlVersion),
                'memo' => "PostgresSQL {$this->requiredPgSqlVersion} or higher is required to run Craft CMS.",
            );
        }
        break;
}

// Only run this requirement check if we're running in the context of Craft.
if (class_exists('Craft')) {
    $requirements[] = $this->webrootRequirement();
    $requirements[] = $this->webAliasRequirement();
}

$requirements = array_merge($requirements, array_filter(array(
    array(
        'name' => 'BCMath extension',
        'mandatory' => true,
        'condition' => extension_loaded('bcmath'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.bc.php">BCMath</a> extension is required.'
    ),
    array(
        'name' => 'ctype extension',
        'mandatory' => true,
        'condition' => extension_loaded('ctype'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.ctype.php">ctype</a> extension is required.',
    ),
    array(
        'name' => 'cURL extension',
        'mandatory' => true,
        'condition' => extension_loaded('curl'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.curl.php">cURL</a> extension is required.',
    ),
    array(
        'name' => 'DOM extension',
        'mandatory' => true,
        'condition' => extension_loaded('dom'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.dom.php">DOM</a> extension is required.',
    ),
    array(
        'name' => 'Fileinfo extension',
        'mandatory' => true,
        'condition' => extension_loaded('fileinfo'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.fileinfo.php">Fileinfo</a> extension required.'
    ),
    array(
        'name' => 'GD extension or ImageMagick extension',
        'mandatory' => false,
        'condition' => extension_loaded('gd') || (extension_loaded('imagick') && !empty(\Imagick::queryFormats())),
        'memo' => 'When using Craft\'s default image transformer, the <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.image.php">GD</a> or <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.imagick.php">ImageMagick</a> extension is required. ImageMagick is recommended as it adds animated GIF support, and preserves 8-bit and 24-bit PNGs during image transforms.'
    ),
    array(
        'name' => 'iconv extension',
        'mandatory' => true,
        'condition' => function_exists('iconv'),
        'memo' => '<a rel="noopener" target="_blank" href="https://php.net/manual/en/book.iconv.php">iconv</a> is required for more robust character set conversion support.',
    ),
    array(
        'name' => 'Intl extension',
        'mandatory' => true,
        'condition' => $this->checkPhpExtensionVersion('intl', '1.0.2', '>='),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.intl.php">Intl</a> extension (version 1.0.2+) is recommended.'
    ),
    array(
        'name' => 'JSON extension',
        'mandatory' => true,
        'condition' => extension_loaded('json'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.json.php">JSON</a> extension is required for JSON encoding and decoding.',
    ),
    array(
        'name' => 'Multibyte String extension (with Function Overloading disabled)',
        'mandatory' => true,
        'condition' => extension_loaded('mbstring') && ini_get('mbstring.func_overload') == 0,
        'memo' => 'Craft CMS requires the <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.mbstring.php">Multibyte String</a> extension with <a rel="noopener" target="_blank" href="https://php.net/manual/en/mbstring.overload.php">Function Overloading</a> disabled in order to run.'
    ),
    array(
        'name' => extension_loaded('opcache') ? 'OPcache extension (with save_comments)' : 'OPcache extension',
        'mandatory' => extension_loaded('opcache'),
        'condition' => extension_loaded('opcache') && ini_get('opcache.save_comments') == 1,
        'memo' => extension_loaded('opcache')
            ? 'The <a rel="noopener" target="_blank" href="https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.save-comments">opcache.save_comments</a> configuration setting must be enabled.'
            : 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.opcache.php">OPcache</a> extension is recommended in production environments.'
    ),
    array(
        'name' => 'OpenSSL extension',
        'mandatory' => true,
        'condition' => extension_loaded('openssl'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.openssl.php">OpenSSL</a> extension is required.'
    ),
    array(
        'name' => 'PCRE extension (with UTF-8 support)',
        'mandatory' => true,
        'condition' => extension_loaded('pcre') && preg_match('/./u', 'Ü') === 1,
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.pcre.php">PCRE</a> extension is required and it must be compiled to support UTF-8.',
    ),
    array(
        'name' => 'PDO extension',
        'mandatory' => true,
        'condition' => extension_loaded('pdo'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.pdo.php">PDO</a> extension is required.'
    ),
    $pdoExtensionRequirement,
    array(
        'name' => 'Reflection extension',
        'mandatory' => true,
        'condition' => extension_loaded('reflection'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/class.reflectionextension.php">Reflection</a> extension is required.',
    ),
    array(
        'name' => 'SPL extension',
        'mandatory' => true,
        'condition' => extension_loaded('SPL'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.spl.php">SPL</a> extension is required.'
    ),
    array(
        'name' => 'Zip extension',
        'mandatory' => true,
        'condition' => extension_loaded('zip'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/book.zip.php">zip</a> extension is required for zip and unzip operations.',
    ),

    array(
        'name' => 'ignore_user_abort()',
        'mandatory' => false,
        'condition' => function_exists('ignore_user_abort'),
        'memo' => '<a rel="noopener" target="_blank" href="https://php.net/manual/en/function.ignore-user-abort.php">ignore_user_abort()</a> must be enabled in your PHP configuration for the native web-based queue runner to work.',
    ),
    array(
        'name' => 'password_hash()',
        'mandatory' => true,
        'condition' => function_exists('password_hash'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/function.password-hash.php">password_hash()</a> function is required so Craft can create secure passwords.',
    ),
    array(
        'name' => 'proc_close()',
        'mandatory' => false,
        'condition' => function_exists('proc_close'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/function.proc-close.php">proc_close()</a> function is required for Plugin Store operations as well as sending emails.',
    ),
    array(
        'name' => 'proc_get_status()',
        'mandatory' => false,
        'condition' => function_exists('proc_get_status'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/function.proc-get-status.php">proc_get_status()</a> function is required for Plugin Store operations as well as sending emails.',
    ),
    array(
        'name' => 'proc_open()',
        'mandatory' => false,
        'condition' => function_exists('proc_open'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/function.proc-open.php">proc_open()</a> function is required for Plugin Store operations as well as sending emails.',
    ),
    array(
        'name' => 'proc_terminate()',
        'mandatory' => false,
        'condition' => function_exists('proc_terminate'),
        'memo' => 'The <a rel="noopener" target="_blank" href="https://php.net/manual/en/function.proc-terminate.php">proc_terminate()</a> function is required for Plugin Store operations as well as sending emails.',
    ),

    array(
        'name' => 'allow_url_fopen',
        'mandatory' => false,
        'condition' => ini_get('allow_url_fopen'),
        'memo' => '<a rel="noopener" target="_blank" href="https://php.net/manual/en/filesystem.configuration.php#ini.allow-url-fopen">allow_url_fopen</a> must be enabled in your PHP configuration for Plugin Store and updating operations.',
    ),

    $this->iniSetRequirement(),
    $this->memoryLimitRequirement(),
    $this->maxExecutionTimeRequirement(),
)));

return $requirements;
