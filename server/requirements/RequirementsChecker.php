<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

if (version_compare(PHP_VERSION, '4.3', '<')) {
    echo 'At least PHP 4.3 is required to run this script!';
    exit(1);
}

/**
 * The Craft Requirement Checker allows checking if the current system meets the minimum requirements for running a
 * Craft 4 application.
 *
 * This class allows rendering of the requirement report through a web browser or command line interface.
 *
 * Example:
 *
 * ~~~php
 * require_once('path/to/RequirementsChecker.php');
 * $requirementsChecker = new RequirementsChecker();
 * $requirements = array(
 *     array(
 *         'name' => 'PHP Some Extension',
 *         'mandatory' => true,
 *         'condition' => extension_loaded('some_extension'),
 *         'memo' => 'PHP extension "some_extension" required',
 *     ),
 * );
 *
 * $requirementsChecker->checkCraft()->check($requirements)->render();
 * ~~~
 *
 * If you wish to render the report with your own representation, use [[getResult()]] instead of [[render()]]
 *
 * Note: this class definition does not match ordinary Craft style, because it should match PHP 4.3
 * and should not use features from newer PHP versions!
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RequirementsChecker
{
    var $dsn;
    var $dbDriver;
    var $dbUser;
    var $dbPassword;

    var $result;

    var $requiredMySqlVersion = '5.7.8';
    var $requiredMariaDbVersion = '10.2.7';
    var $requiredPgSqlVersion = '10.0';

    /**
     * Check the given requirements, collecting results into internal field.
     * This method can be invoked several times checking different requirement sets.
     * Use [[getResult()]] or [[render()]] to get the results.
     *
     * @param array|string $requirements The requirements to be checked. If an array, it is treated as the set of
     *                                   requirements. If a string, it is treated as the path of the file, which
     *                                   contains the requirements;
     *
     * @return static self reference
     */
    function check($requirements)
    {
        if (is_string($requirements)) {
            $requirements = require $requirements;
        }

        if (!is_array($requirements)) {
            $this->usageError('Requirements must be an array, "'.gettype($requirements).'" has been given!');
        }

        if (!isset($this->result) || !is_array($this->result)) {
            $this->result = array(
                'summary' => array(
                    'total' => 0,
                    'errors' => 0,
                    'warnings' => 0,
                ),
                'requirements' => array(),
            );
        }

        foreach ($requirements as $key => $rawRequirement) {
            $requirement = $this->normalizeRequirement($rawRequirement, $key);
            $this->result['summary']['total']++;

            if (!$requirement['condition']) {
                if ($requirement['mandatory']) {
                    $requirement['error'] = true;
                    $requirement['warning'] = true;
                    $this->result['summary']['errors']++;
                } else {
                    $requirement['error'] = false;
                    $requirement['warning'] = true;
                    $this->result['summary']['warnings']++;
                }
            } else {
                $requirement['error'] = false;
                $requirement['warning'] = false;
            }

            $this->result['requirements'][] = $requirement;
        }

        return $this;
    }

    /**
     * Performs the check for the Craft core requirements.
     *
     * @return static self reference
     */
    function checkCraft()
    {
        return $this->check(dirname(__FILE__).DIRECTORY_SEPARATOR.'requirements.php');
    }

    /**
     * Return the check results.
     *
     * The results will be returned in this format:
     *
     * ```php
     * array(
     *     'summary' => array(
     *         'total' => total number of checks,
     *         'errors' => number of errors,
     *         'warnings' => number of warnings,
     *     ),
     *     'requirements' => array(
     *         array(
     *             ...
     *             'error' => is there an error,
     *             'warning' => is there a warning,
     *         ),
     *         // ...
     *     ),
     * )
     * ```
     *
     * @return array|null The check results
     */
    function getResult()
    {
        if (isset($this->result)) {
            return $this->result;
        }

        return null;
    }

    /**
     * Renders the requirements check result. The output will vary depending is a script running from web or from console.
     */
    function render()
    {
        if (!isset($this->result)) {
            $this->usageError('Nothing to render!');
        }

        $baseViewFilePath = dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'views';

        if (!empty($_SERVER['argv'])) {
            $viewFilename = $baseViewFilePath.DIRECTORY_SEPARATOR.'console'.DIRECTORY_SEPARATOR.'index.php';
        } else {
            $viewFilename = $baseViewFilePath.DIRECTORY_SEPARATOR.'web'.DIRECTORY_SEPARATOR.'index.php';
        }

        $this->renderViewFile($viewFilename, $this->result);
    }

    /**
     * Checks if the given PHP extension is available and its version matches the given one.
     *
     * @param string $extensionName The PHP extension name.
     * @param string $version       The required PHP extension version.
     * @param string $compare       The comparison operator, by default '>='.
     *
     * @return bool Whether the PHP extension version matches
     */
    function checkPhpExtensionVersion($extensionName, $version, $compare = '>=')
    {
        if (!extension_loaded($extensionName)) {
            return false;
        }

        $extensionVersion = phpversion($extensionName);

        if (empty($extensionVersion)) {
            return false;
        }

        if (strncasecmp($extensionVersion, 'PECL-', 5) === 0) {
            $extensionVersion = substr($extensionVersion, 5);
        }

        return version_compare($extensionVersion, $version, $compare);
    }

    /**
     * Checks if the given PHP configuration option (from php.ini) is on.
     *
     * @param string $name The configuration option name.
     *
     * @return bool Whether the option is on
     */
    function checkPhpIniOn($name)
    {
        $value = ini_get($name);

        if (empty($value)) {
            return false;
        }

        return ((int)$value === 1 || strtolower($value) === 'on');
    }

    /**
     * Checks if the given PHP configuration option (from php.ini) is off.
     *
     * @param string $name The configuration option name.
     *
     * @return bool Whether the option is off
     */
    function checkPhpIniOff($name)
    {
        $value = ini_get($name);

        if (empty($value)) {
            return true;
        }

        return (strtolower($value) === 'off');
    }

    /**
     * Gets the size in bytes from verbose size representation. For example: '5K' => 5 * 1024
     *
     * @param string $value The verbose size representation.
     *
     * @return int|float The actual size in bytes
     */
    function getByteSize($value)
    {
        // Copied from craft\helpers\App::phpConfigValueInBytes()
        if (!preg_match('/(\d+)(K|M|G)/i', $value, $matches)) {
            return (int)$value;
        }

        $value = (int)$matches[1];

        // Multiply!
        switch (strtolower($matches[2])) {
            case 'g':
                $value *= 1024;
            // no break
            case 'm':
                $value *= 1024;
            // no break
            case 'k':
                $value *= 1024;
            // no break
        }

        return $value;
    }

    /**
     * Renders a view file.
     * This method includes the view file as a PHP script and captures the display result if required.
     *
     * @param string  $_viewFile_ The view file.
     * @param array   $_data_     The data to be extracted and made available to the view file.
     * @param boolean $_return_   Whether the rendering result should be returned as a string.
     *
     * @return string|null The rendering result, or `null` if the rendering result is not required
     */
    function renderViewFile($_viewFile_, $_data_ = null, $_return_ = false)
    {
        // we use special variable names here to avoid conflict when extracting data
        if (is_array($_data_)) {
            extract($_data_, EXTR_PREFIX_SAME, 'data');
        }

        if ($_return_) {
            ob_start();
            ob_implicit_flush(false);

            require $_viewFile_;

            return ob_get_clean();
        }

        require $_viewFile_;

        return null;
    }

    /**
     * Normalizes requirement ensuring it has correct format.
     *
     * @param array   $requirement    The raw requirement.
     * @param integer $requirementKey The requirement key in the list.
     *
     * @return array The normalized requirement
     */
    function normalizeRequirement($requirement, $requirementKey = 0)
    {
        if (!is_array($requirement)) {
            $this->usageError('Requirement must be an array!');
        }

        if (!array_key_exists('condition', $requirement)) {
            $this->usageError("Requirement '{$requirementKey}' has no condition!");
        }

        if (!array_key_exists('name', $requirement)) {
            $requirement['name'] = is_numeric($requirementKey) ? 'Requirement #'.$requirementKey : $requirementKey;
        }

        if (!array_key_exists('mandatory', $requirement)) {
            if (array_key_exists('required', $requirement)) {
                $requirement['mandatory'] = $requirement['required'];
            } else {
                $requirement['mandatory'] = false;
            }
        }

        if (!array_key_exists('memo', $requirement)) {
            $requirement['memo'] = '';
        }

        return $requirement;
    }

    /**
     * Displays a usage error. This method will then terminate the execution of the current application.
     *
     * @param string $message the error message
     */
    function usageError($message)
    {
        echo "Error: $message\n\n";
        exit(1);
    }

    /**
     * Returns the server information.
     *
     * @return string The server information
     */
    function getServerInfo()
    {
        return isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : '';
    }

    /**
     * Returns the current date if possible in string representation.
     *
     * @return string The current date
     */
    function getCurrentDate()
    {
        return @strftime('%Y-%m-%d %H:%M', time());
    }

    /**
     * Error-handler that mutes errors.
     */
    function muteErrorHandler()
    {
    }

    /**
     * @return PDO|false
     */
    function getDbConnection()
    {
        static $conn;

        if ($conn === null) {
            try {
                $conn = new PDO($this->dsn, $this->dbUser, $this->dbPassword);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                $conn = false;
            }
        }

        return $conn;
    }

    /**
     * @param PDO    $conn
     * @param string $requiredVersion
     *
     * @return bool
     * @throws Exception
     */
    function checkDatabaseServerVersion($conn, $requiredVersion)
    {
        return version_compare($conn->getAttribute(PDO::ATTR_SERVER_VERSION), $requiredVersion, '>=');
    }

    /**
     * Checks to see if the MySQL InnoDB storage engine is installed and enabled.
     *
     * @param PDO $conn
     * @return bool
     */
    function isInnoDbSupported($conn)
    {
        $results = $conn->query('SHOW ENGINES');

        foreach ($results as $result) {
            if (strtolower($result['Engine']) === 'innodb' && strtolower($result['Support']) !== 'no') {
                return true;
            }
        }

        return false;
    }

    /**
     * This method attempts to see if MySQL timezone data has been populated on
     * the MySQL server Craft is configured to use.
     *
     * https://dev.mysql.com/doc/refman/5.7/en/time-zone-support.html
     *
     * @param PDO $conn
     * @return bool
     */
    function validateDatabaseTimezoneSupport($conn)
    {
        $query = $conn->query("SELECT CONVERT_TZ('2007-03-11 2:00:00','US/Eastern','US/Central') AS time1");
        $result = $query->fetchColumn();

        if (!$result) {
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    function iniSetRequirement()
    {
        $oldValue = ini_get('memory_limit');

        $setValue = '442M'; // A random PHP memory limit value.
        if ($oldValue !== '-1'){
            // When the old value is not equal to '-1', add 1MB to the limit set at the moment.
            $bytes = $this->getByteSize($oldValue) + $this->getByteSize('1M');
            $setValue = sprintf('%sM', $bytes / (1024 * 1024));
        }

        set_error_handler(array($this, 'muteErrorHandler'));
        $result = ini_set('memory_limit', $setValue);
        $newValue = ini_get('memory_limit');
        ini_set('memory_limit', $oldValue);
        restore_error_handler();

        $mandatory = true;

        // ini_set can return false or an empty string depending on your php version / FastCGI.
        // If ini_set has been disabled in php.ini, the value will be null because of our muted error handler
        if ($result === null) {
            $memo = 'It looks like <a rel="noopener" target="_blank" href="https://php.net/manual/en/function.ini-set.php">ini_set</a> has been disabled in your <code>php.ini</code> file. Craft requires that to operate.';
            $condition = false;
        }

        // ini_set can return false or an empty string or the current value of memory_limit depending on your php
        // version and FastCGI. Regard, calling it didn't work, but there was no error.
        else if ($result === false || $result === '' || $result === $newValue) {
            $memo = 'It appears calls to <a rel="noopener" target="_blank" href="https://php.net/manual/en/function.ini-set.php">ini_set</a> are not working for Craft. You may need to increase some settings in your php.ini file such as <a rel="noopener" target="_blank" href="https://php.net/manual/en/ini.core.php#ini.memory-limit">memory_limit</a> and <a rel="noopener" target="_blank" href="https://php.net/manual/en/info.configuration.php#ini.max-execution-time">max_execution_time</a> for long running operations like updating and asset transformations.';

            // Set mandatory to false here so it's not a "fatal" error, but will be treated as a warning.
            $mandatory = false;
            $condition = false;
        } else {
            $memo = 'Calls to <a rel="noopener" target="_blank" href="https://php.net/manual/en/function.ini-set.php">ini_set</a> are working correctly.';
            $condition = true;
        }

        return array(
            'name' => 'ini_set calls',
            'mandatory' => $mandatory,
            'condition' => $condition,
            'memo' => $memo,
        );
    }

    /**
     * @return array
     *
     * @see https://php.net/manual/en/ini.core.php#ini.memory-limit
     */
    function memoryLimitRequirement()
    {
        $memoryLimit = ini_get('memory_limit');
        $bytes = $this->getByteSize($memoryLimit);

        $humanLimit = $memoryLimit . ($memoryLimit === -1 ? ' (no limit)' : '');
        $memo = "Craft requires a minimum PHP memory limit of 256M. The memory_limit directive in php.ini is currently set to {$humanLimit}.";

        return array(
            'name' => 'Memory Limit',
            'mandatory' => false,
            'condition' => $bytes === -1 || $bytes >= 268435456,
            'memo' => $memo,
        );
    }

    /**
     * @return array
     *
     * @see https://php.net/manual/en/info.configuration.php#ini.max-execution-time
     */
    function maxExecutionTimeRequirement()
    {
        $maxExecutionTime = (int)trim(ini_get('max_execution_time'));

        $humanTime = $maxExecutionTime . ($maxExecutionTime === 0 ? ' (no limit)' : '');
        $memo = "Craft requires a minimum PHP max execution time of 120 seconds. The max_execution_time directive in php.ini is currently set to {$humanTime}.";

        return array(
            'name' => 'Max Execution Time',
            'mandatory' => false,
            'condition' => $maxExecutionTime === 0 || $maxExecutionTime >= 120,
            'memo' => $memo,
        );
    }

    /**
     * @return array
     */
    function webAliasRequirement()
    {
        $aliases = Craft::$app->getConfig()->getGeneral()->aliases;
        $memo = 'We recommend explicitly overriding the <a rel="noopener" target="_blank" href="https://craftcms.com/docs/3.x/config/#aliases">@web alias</a>.';
        $pass = false;

        if (isset($aliases['web']) || isset($aliases['@web'])) {
            $memo = 'Your @web alias is set correctly';
            $pass = true;
        }

        return array(
            'name' => 'Ensure @web alias is explicitly overridden',
            'mandatory' => false,
            'condition' => $pass,
            'memo' => $memo,
        );
    }

    /**
     * @return array
     */
    function webrootRequirement()
    {
        $pathService = Craft::$app->getPath();
        $folders = array(
            'config' => $pathService->getConfigPath(),
            'storage' => $pathService->getStoragePath(),
            'templates' => $pathService->getSiteTemplatesPath(),
            'translations' => $pathService->getSiteTranslationsPath(),
            'vendor' => $pathService->getVendorPath(),
        );

        // figure out which ones are public
        $publicFolders = array();
        foreach ($folders as $key => $path) {
            if (
                $path &&
                ($realPath = realpath($path)) &&
                $this->isPathInsideWebroot($realPath)
            ) {
                $publicFolders[] = $key;
            }
        }

        if ($condition = empty($publicFolders)) {
            $memo = 'All of your Craft folders appear to be above your web root.';
        } else {
            $total = count($publicFolders);
            $folderString = '';

            foreach ($publicFolders as $i => $folder) {
                if ($total >= 3 && $i > 0) {
                    $folderString .= ', ';
                    if ($i === $total - 2) {
                        $folderString .= 'and ';
                    }
                } else if ($total === 2 && $i === 1) {
                    $folderString .= ' and ';
                }

                $folderString .= "<code>{$folder}/</code>";
            }

            if ($total > 1) {
                $memo = "Your {$folderString} folders appear to be publicly accessible, which is a security risk. They should be moved above your web root.";
            } else {
                $memo = "Your {$folderString} folder appears to be publicly accessible, which is a security risk. It should be moved above your web root.";
            }
        }

        return array(
            'name' => 'Sensitive folders should not be publicly accessible',
            'mandatory' => false,
            'condition' => $condition,
            'memo' => $memo,
        );
    }

    /**
     * @param string $pathToTest
     *
     * @return bool
     */
    function isPathInsideWebroot($pathToTest)
    {
        // If the path is empty, the folder doesn't even exist.
        if ($pathToTest) {
            // Get the base path without the script name.
            $subBasePath = \craft\helpers\FileHelper::normalizePath(mb_substr(Craft::$app->getRequest()->getScriptFile(), 0, -mb_strlen(Craft::$app->getRequest()->getScriptUrl())));

            // If $subBasePath === '', then both the craft folder and index.php are living at the root of the filesystem.
            // Note that some web servers (Idea Web Server) can be configured with virtual roots so that PHP's realpath
            // returns that instead of the actual root.
            if ($subBasePath === '' || mb_strpos($pathToTest, $subBasePath) !== false) {
                return true;
            }
        }

        return false;
    }
}
