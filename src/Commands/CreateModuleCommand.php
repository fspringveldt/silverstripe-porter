<?php

namespace SilverStripe\Porter\Commands;

use SilverStripe\Porter\Helpers\ValidationHelper;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\RuntimeException;

/**
 * Class CreateModuleCommand
 */
class CreateModuleCommand extends Command
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
            . "at {$this->getModulePath()}"
        );
        $this->preCopyOptions($input);
        $this->copySkeleton();
        $output->writeln(' - Skeleton copied');
        $this->setupComposerJson();
        $output->writeln(' - composer.json updated');
        $this->postCopyOptions($input);
        $output->writeln(' - Options copied');
        $output->writeln(' - Done');
    }

    /**
     * Applies any pre copy options
     * @param InputInterface $input
     */
    protected function preCopyOptions(InputInterface $input)
    {
        if ($input->getOption(self::OPTIONS_NON_VENDOR)) {
            $this->moduleType = 'silverstripe-module';
        }

        if ($ss3 = $input->getOption(self::OPTIONS_SS3)) {
            $this->frameworkVersion = 'ss3';
        }
    }

    /**
     * Applies any post copy options
     * @param InputInterface $input
     */
    protected function postCopyOptions(InputInterface $input)
    {
        $source = $this->getSourcePath('options');
        $target = $this->getTargetPath();
        $uri = function ($folder, $endPoint) {
            return "{$folder}{$this->separator}{$endPoint}";
        };

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
     * Sets the argument values to their respective properties
     * @param InputInterface $input
     * @throws RuntimeException
     */
    protected function setArguments(InputInterface $input)
    {
        $this->moduleName = $input->getArgument(self::ARGUMENTS_MODULE_NAME);
        $this->namespace = $input->getArgument(self::ARGUMENTS_MODULE_NAMESPACE);

        ValidationHelper::validateModuleName($this->moduleName);
        ValidationHelper::validateNamespace($this->namespace);
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

        $contents = str_ireplace($search, $replace, $this->getComposerFileContents());
        $this->getFilesystem()->dumpFile($this->getComposerFilePath(), $contents);
    }

    /**
     * Returns the path to the composer file
     * @return string
     */
    public function getComposerFilePath()
    {
        return $this->getTargetPath() . DIRECTORY_SEPARATOR . 'composer.json';
    }

    /**
     * Returns the content of the composer file
     * @return bool|string
     */
    public function getComposerFileContents()
    {
        return file_get_contents($this->getComposerFilePath());
    }

    /**
     * Gets or sets the file system property
     * @return Filesystem
     */
    public function getFilesystem()
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
    public function getSourcePath($subDir = 'assets')
    {
        $porterDir = __DIR__;
        return "{$porterDir}{$this->separator}{$subDir}{$this->separator}{$this->frameworkVersion}-skeleton";
    }

    /**
     * Gets destination path for the module skeleton
     * @return string
     */
    public function getTargetPath()
    {
        $folderName = substr($this->moduleName, stripos($this->moduleName, DIRECTORY_SEPARATOR) + 1);
        return $this->getModulePath() . $this->separator . $folderName;
    }

    /**
     * Returns the module path. Defaults to use getcwd();
     * @return string
     */
    public function getModulePath()
    {
        if (!$this->modulePath) {
            $this->modulePath = getcwd();
        }
        return $this->modulePath;
    }

    /**
     * @param string $modulePath
     * @return CreateModuleCommand
     */
    public function setModulePath($modulePath)
    {
        $this->modulePath = $modulePath;
        return $this;
    }
}