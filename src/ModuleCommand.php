<?php

namespace SilverStripe\Porter;

use GuzzleHttp\Client;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Yaml\Exception\RuntimeException;

/**
 * Class ModuleCommand
 */
class ModuleCommand extends Command
{
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
    private $moduleType;

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
     * Configures the command
     */
    protected function configure()
    {
        $this->setName('module')
            ->setDescription('Sets up a new SilverStripe module skeleton at a path you specify (defaults to your current projects BASE_PATH)')
            ->addArgument('module-name', InputArgument::REQUIRED)
            ->addArgument('module-namespace', InputArgument::REQUIRED)
            ->addArgument('module-path', InputArgument::OPTIONAL)
            ->addArgument('ss-version', InputArgument::OPTIONAL)
            ->addOption(
                'isVendor',
                null,
                InputOption::VALUE_REQUIRED,
                'Installs as a vendor module',
                true
            );
    }

    /**
     * Executes this command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->modulePath = $input->getArgument('module-path');
        $this->moduleName = $input->getArgument('module-name');
        $this->namespace = $input->getArgument('module-namespace');
        $this->moduleType = 'silverstripe-module';

        if ($input->getOption('isVendor')) {
            $this->moduleType = 'silverstripe-vendormodule';
        }

        if (stripos($this->moduleName, DIRECTORY_SEPARATOR) === false) {
            throw new RuntimeException('Invalid module name given. Use the format module/name');
        }


        $this->copySkeleton();
        $this->setupComposerJson();
    }

    protected function copySkeleton()
    {
        $source = $this->getSourcePath();
        $this->getFilesystem()->mirror($source, $this->getTargetPath());
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
     * Returns the path to the module skeleton
     * @return string
     */
    protected function getSourcePath()
    {
        $separator = DIRECTORY_SEPARATOR;
        $porterDir = __DIR__;
        return "{$porterDir}{$separator}assets{$separator}{$this->frameworkVersion}-skeleton";
    }

    /**
     * Gets destination path for the module skeleton
     * @return string
     */
    protected function getTargetPath()
    {
        $folderName = substr($this->moduleName, stripos($this->moduleName, DIRECTORY_SEPARATOR) + 1);
        return BASE_PATH . DIRECTORY_SEPARATOR . $folderName;
    }
}