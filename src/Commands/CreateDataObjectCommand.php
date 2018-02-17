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
 * Class CreateDataObjectCommand
 */
class CreateDataObjectCommand extends Command
{
    const ARGUMENTS_NAME = 'name';
    const ARGUMENTS_NAMESPACE = 'namespace';
    const ARGUMENTS_PATH = 'path';
    const OPTIONS_HAS_ONE = 'withHasOne';
    const OPTIONS_HAS_MANY = 'withHasMany';
    const OPTIONS_MANY_MANY = 'withManyMany';

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $namespace;

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
        $this->setName('create-dataobject')
            ->setDescription('Sets up a new SilverStripe dataobject skeleton at the'
                . ' given path.')
            ->addArgument(self::ARGUMENTS_NAME, InputArgument::REQUIRED)
            ->addArgument(self::ARGUMENTS_PATH, InputArgument::REQUIRED)
            ->addArgument(self::ARGUMENTS_NAMESPACE, InputArgument::REQUIRED)
            ->addOption(self::OPTIONS_HAS_ONE, null, InputOption::VALUE_NONE)
            ->addOption(self::OPTIONS_HAS_MANY, null, InputOption::VALUE_NONE)
            ->addOption(self::OPTIONS_MANY_MANY, null, InputOption::VALUE_NONE);
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
            "Creating SilverStripe DataObject named {$this->name} "
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
        $this->name = $input->getArgument(self::ARGUMENTS_NAME);
        $this->namespace = $input->getArgument(self::ARGUMENTS_NAMESPACE);
        $this->modulePath = $input->getArgument(self::ARGUMENTS_PATH);

        ValidationHelper::validateNamespace($this->namespace);
        ValidationHelper::validateModuleName($this->moduleName);
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
        if ($input->getOption(self::OPTIONS_HAS_ONE)) {
            $this->moduleType = 'silverstripe-module';
        }

        if ($ss3 = $input->getOption(self::OPTIONS_SS3)) {
            $this->frameworkVersion = 'ss3';
        }

        if ($withTravis = $input->getOption(self::OPTIONS_HAS_MANY)) {
            $file = '.travis.yml';
            $this->getFilesystem()->copy(
                $uri($source, $file),
                $uri($target, $file)
            );
        }

        if ($withCircleCI = $input->getOption(self::OPTIONS_MANY_MANY)) {
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
            $this->name,
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
        $folderName = substr($this->name, stripos($this->name, DIRECTORY_SEPARATOR) + 1);
        return $this->modulePath . $this->separator . $folderName;
    }
}
