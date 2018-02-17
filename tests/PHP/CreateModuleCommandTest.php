<?php

namespace SilverStripe\Porter\Tests;

use PHPUnit\Framework\TestCase;
use SilverStripe\Assets\Filesystem;
use SilverStripe\Porter\Commands\CreateModuleCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Exception\RuntimeException;

/**
 * Class CreateModuleCommandTest
 */
class CreateModuleCommandTest extends TestCase
{
    protected static $fixture_file = '';

    /**
     * @var CreateModuleCommand
     */
    private $command;

    protected function setUp()
    {
        parent::setUp();
        $this->command = new CreateModuleCommand();
    }

    public function testCommandIsConfiguredCorrectly()
    {
        $this->assertEquals('create-module', $this->command->getName());
        $this->assertContains(
            'Sets up a new SilverStripe module skeleton at',
            $this->command->getDescription()
        );
        $def = $this->command->getDefinition();
        $this->assertTrue($def->hasArgument(CreateModuleCommand::ARGUMENTS_MODULE_NAME));
        $this->assertTrue($def->hasArgument(CreateModuleCommand::ARGUMENTS_MODULE_NAMESPACE));
        $this->assertTrue($def->hasOption(CreateModuleCommand::OPTIONS_NON_VENDOR));
        $this->assertTrue($def->hasOption(CreateModuleCommand::OPTIONS_SS3));
        $this->assertTrue($def->hasOption(CreateModuleCommand::OPTIONS_TRAVIS_CI));
        $this->assertTrue($def->hasOption(CreateModuleCommand::OPTIONS_CIRCLE_CI));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Invalid module name given. Use the format module/name
     */
    public function testInvalidModuleNmeThrowsException()
    {
        $tester = new CommandTester($this->command);
        $input = [
            'module-name' => 'foo',
            'module-namespace' => 'Foo'
        ];
        $tester->execute($input);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage It seems your namespace is formed incorrectly.
     */
    public function testInvalidNameSpaceThrowsException()
    {
        $tester = new CommandTester($this->command);
        $input = [
            'module-name' => 'foo/bar',
            'module-namespace' => 'Foo'
        ];
        $tester->execute($input);
    }

    /**
     *
     */
    public function testComposerVariablesGetsSet()
    {
        $tester = new CommandTester($this->command);
        $input = [
            'module-name' => 'foo/bar',
            'module-namespace' => 'Foo\\\\Bar'
        ];
        $tester->execute($input);
        $composerContents = $this->command->getComposerFileContents();
        $this->assertNotContains('$moduleName', $composerContents);
        $this->assertNotContains('$namespace', $composerContents);
        $this->assertNotContains('$moduleType', $composerContents);
        $this->deleteDirectory($this->command->getTargetPath());
    }

    /**
     * Recursively deletes a directory
     * @param $dir
     * @return bool
     */
    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }

        }

        return rmdir($dir);
    }
}