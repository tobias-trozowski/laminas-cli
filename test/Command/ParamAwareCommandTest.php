<?php

/**
 * @see       https://github.com/laminas/laminas-cli for the canonical source repository
 * @copyright https://github.com/laminas/laminas-cli/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-cli/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Cli\Command;

use Laminas\Cli\Input\BoolParam;
use Laminas\Cli\Input\ParamAwareInputInterface;
use LaminasTest\Cli\TestAsset\ParamAwareCommandStub;
use LaminasTest\Cli\TestAsset\ParamAwareCommandStubNonHinted;
use PackageVersions\Versions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function str_replace;
use function strstr;

class ParamAwareCommandTest extends TestCase
{
    /** @var ParamAwareCommandStub|ParamAwareCommandStubNonHinted */
    private $command;

    /**
     * @var QuestionHelper|MockObject
     * @psalm-var QuestionHelper&MockObject
     */
    private $questionHelper;

    public function setUp(): void
    {
        $this->questionHelper = $this->createMock(QuestionHelper::class);

        /** @psalm-var HelperSet&MockObject $helperSet */
        $helperSet = $this->createMock(HelperSet::class);
        $helperSet
            ->expects($this->any())
            ->method('get')
            ->with(
                $this->equalTo('question')
            )
            ->willReturn($this->questionHelper);

        /** @psalm-suppress DeprecatedClass */
        $consoleVersion = strstr(Versions::getVersion('symfony/console'), '@', true) ?: '';
        $commandClass   = str_replace('v', '', $consoleVersion) >= '5.0.0'
            ? ParamAwareCommandStub::class
            : ParamAwareCommandStubNonHinted::class;

        $this->command = new $commandClass($helperSet);
    }

    public function testAddParamProxiesToAddOption(): void
    {
        $param = (new BoolParam('test'))
            ->setDescription('Yes or no')
            ->setDefault(false)
            ->setShortcut('t')
            ->setRequiredFlag(true);

        $this->assertSame($this->command, $this->command->addParam($param));

        $this->assertArrayHasKey('test', $this->command->options);

        $option = $this->command->options['test'];
        $this->assertIsArray($option);
        $this->assertSame($param->getShortcut(), $option['shortcut']);
        $this->assertSame($param->getOptionMode(), $option['mode']);
        $this->assertSame($param->getDescription(), $option['description']);
        $this->assertNull($option['default']); // Option default is always null!
    }

    public function testRunDecoratesInputInParameterAwareInputInstance(): void
    {
        /** @psalm-var InputInterface&MockObject $input */
        $input = $this->createMock(InputInterface::class);
        /** @psalm-var OutputInterface&MockObject $output */
        $output = $this->createMock(OutputInterface::class);
        $param  = (new BoolParam('test'))
            ->setDescription('Yes or no')
            ->setDefault(false)
            ->setShortcut('t')
            ->setRequiredFlag(true);

        $this->command->addParam($param);
        $this->assertSame(0, $this->command->run($input, $output));

        $this->assertInstanceOf(ParamAwareInputInterface::class, $this->command->input);
        $this->assertSame($output, $this->command->output);
    }
}
