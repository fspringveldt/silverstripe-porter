<?php

namespace SilverStripe\Porter;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\RuntimeException;

/**
 * Class ModuleCommand
 */
class ModuleCommand extends Command
{
    const ARGUMENTS_MODULE_NAME = 'module-name';
    const ARGUMENTS_MODULE_NAMESPACE = 'module-namespace';
    const ARGUMENTS_MODULE_PATH = 'module-path';
    const OPTIONS_SS3 = 'ss3';
    const OPTIONS_NON_VENDOR = 'nonVendor';
    const OPTIONS_TRAVIS_CI = 'withTravisCI';
    const OPTIONS_CIRCLE_CI = 'withCircleCI';

    /**
     * @var string
     */
    private $moduleName;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $moduleType = 'silverstripe-vendormodule';

    /**
     * @var string
     */
    private $modulePath;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var string The target frameowkr version
     */
    private $frameworkVersion = 'ss4';

    /**
     * @var string
     */
    private $separator = DIRECTORY_SEPARATOR;

    /**
     * Configures the command
     */
    protected function configure()
    {
        $this->setName('create-module')
            ->setDescription('Sets up a new SilverStripe module skeleton at a'
                . ' path you specify (defaults to using getcwd())')
            ->addArgument(self::ARGUMENTS_MODULE_NAME, InputArgument::REQUIRED)
            ->addArgument(self::ARGUMENTS_MODULE_NAMESPACE, InputArgument::REQUIRED)
            ->addOption(self::OPTIONS_NON_VENDOR, null, InputOption::VALUE_NONE)
            ->addOption(self::OPTIONS_SS3, null, InputOption::VALUE_NONE)
            ->addOption(self::OPTIONS_TRAVIS_CI, null, InputOption::VALUE_NONE)
            ->addOption(self::OPTIONS_CIRCLE_CI, null, InputOption::VALUE_NONE);
    }

    /**
     * Executes this command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setArguments($input);
        $output->writeln(
            "Creating SilverStripe module named {$this->moduleName} "
            . "at {$this->modulePath}"
        );
        $this->copySkeleton();
        $output->writeln(' - Skeleton copied');
        $this->setupComposerJson();
        $output->writeln(' - composer.json updated');
        $this->copyOptions($input);
        $output->writeln(' - Options copied');
        $output->writeln(' - Done');
    }

    /**
     * Sets the argument values to their respective properties
     * @param InputInterface $input
     * @throws RuntimeException
     */
    protected function setArguments(InputInterface $input)
    {
        $this->modulePath = getcwd();
        $this->moduleName = $input->getArgument(self::ARGUMENTS_MODULE_NAME);
        $this->namespace = $input->getArgument(self::ARGUMENTS_MODULE_NAMESPACE);

        if (in_array(substr_count($this->namespace, '\\'), [0, 1])) {
            $message = "It seems your namespace is formed incorrectly.\n"
                . "Possible examples are NameSpace\\\\ or NameSpace\\\\Folder\\\\\n"
                . "[Double backslashes]";
            throw new RuntimeException($message);
        }

        if (stripos($this->moduleName, DIRECTORY_SEPARATOR) === false) {
            throw new RuntimeException('Invalid module name given. Use the format module/name');
        }
    }

    /**
     * Checks for and actions all actions
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function copyOptions(InputInterface $input)
    {
        $source = $this->getSourcePath('options');
        $target = $this->getTargetPath();
        $uri = function ($folder, $endPoint) {
            return "{$folder}{$this->separator}{$endPoint}";
        };
        if ($input->getOption(self::OPTIONS_NON_VENDOR)) {
            $this->moduleType = 'silverstripe-module';
        }

        if ($ss3 = $input->getOption(self::OPTIONS_SS3)) {
            $this->frameworkVersion = 'ss3';
        }

        if ($withTravis = $input->getOption(self::OPTIONS_TRAVIS_CI)) {
            $file = '.travis.yml';
            $this->getFilesystem()->copy(
                $uri($source, $file),
                $uri($target, $file)
            );
        }

        if ($withCircleCI = $input->getOption(self::OPTIONS_CIRCLE_CI)) {
            $folder = '.circleci';
            $this->getFilesystem()->mirror(
                $uri($source, $folder),
                $uri($target, $folder)
            );
        }
    }

    /**
     * Copies the skeleton to the root directory
     */
    protected function copySkeleton()
    {
        $this->getFilesystem()->mirror(
            $this->getSourcePath(),
            $this->getTargetPath()
        );
    }

    /**
     * Copies the configured values to the composer.json file
     */
    protected function setupComposerJson()
    {
        $path = $this->getTargetPath() . DIRECTORY_SEPARATOR . 'composer.json';
        $contents = file_get_contents($path);
        $search = [
            '$moduleName',
            '$moduleType',
            '$namespace'
        ];
        $replace = [
            $this->moduleName,
            $this->moduleType,
            $this->namespace
        ];

        $contents = str_ireplace($search, $replace, $contents);
        $this->getFilesystem()->dumpFile($path, $contents);
    }

    /**
     * Gets or sets the file system property
     * @return Filesystem
     */
    protected function getFilesystem()
    {
        if (!$this->fileSystem) {
            $this->fileSystem = new Filesystem();
        }

        return $this->fileSystem;
    }

    /**
     * Returns the path to the given sub-dir. Defaults to assets skeleton
     * @param string $subDir
     * @return string
     */
    protected function getSourcePath($subDir = 'assets')
    {
        $porterDir = __DIR__;
        return "{$porterDir}{$this->separator}{$subDir}{$this->separator}{$this->frameworkVersion}-skeleton";
    }

    /**
     * Gets destination path for the module skeleton
     * @return string
     */
    protected function getTargetPath()
    {
        $folderName = substr($this->moduleName, stripos($this->moduleName, DIRECTORY_SEPARATOR) + 1);
        return $this->modulePath . $this->separator . $folderName;
    }
}