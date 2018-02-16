<?php

namespace SilverStripe\Porter\Tests;

use PHPUnit\Framework\TestCase;
use SilverStripe\Porter\Commands\CreateModuleCommand;

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
}