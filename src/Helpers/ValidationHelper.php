<?php

namespace SilverStripe\Porter\Helpers;

use Symfony\Component\Yaml\Exception\RuntimeException;

/**
 * Class ValidationHelper
 */
class ValidationHelper
{
    /**
     * Validates the format of the given namespace
     * @param $namespace
     * @return bool
     * @throws RuntimeException
     */
    public static function validateNamespace($namespace)
    {
        if (in_array(substr_count($namespace, '\\'), [0, 1])) {
            $message = "It seems your namespace is formed incorrectly.\n"
                . "Possible examples are NameSpace\\\\ or NameSpace\\\\Folder\\\\\n"
                . "[Double backslashes]";
            throw new RuntimeException($message);
        }

        return true;
    }

    /**
     * Validates the format of the given module name
     * @param $moduleName
     * @return bool
     * @throws RuntimeException
     */
    public static function validateModuleName($moduleName)
    {
        if (stripos($moduleName, DIRECTORY_SEPARATOR) === false) {
            throw new RuntimeException('Invalid module name given. Use the format module/name');
        }

        return true;
    }
}