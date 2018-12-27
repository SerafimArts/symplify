<?php declare(strict_types=1);

namespace Symplify\Autodiscovery\Command;

use Nette\Utils\FileSystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Symplify\Autodiscovery\Yaml\ExplicitToAutodiscoveryConverter;
use Symplify\PackageBuilder\Console\Command\CommandNaming;
use Symplify\PackageBuilder\Console\ShellCode;
use Symplify\PackageBuilder\DependencyInjection\CompilerPass\AutowireSinglyImplementedCompilerPass;
use function Safe\realpath;
use function Safe\sprintf;

final class ConvertYamlCommand extends Command
{
    /**
     * @var string
     */
    private const OPTION_REMOVE_SINGLY_IMPLEMENTED = 'remove-singly-implemented';

    /**
     * @var string
     */
    private const OPTION_NESTING_LEVEL = 'nesting-level';

    /**
     * @var ExplicitToAutodiscoveryConverter
     */
    private $explicitToAutodiscoveryConverter;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    public function __construct(
        ExplicitToAutodiscoveryConverter $explicitToAutodiscoveryConverter,
        SymfonyStyle $symfonyStyle
    ) {
        parent::__construct();
        $this->explicitToAutodiscoveryConverter = $explicitToAutodiscoveryConverter;
        $this->symfonyStyle = $symfonyStyle;
    }

    protected function configure(): void
    {
        $this->setName(CommandNaming::classToName(self::class));
        $this->setDescription(
            'Convert "services.yml" from pre-Symfony 3.3 format to modern format using autodiscovery, autowire and autoconfigure'
        );

        $this->addArgument('file', InputArgument::REQUIRED, 'Path to "services.yml" to convert');

        $this->addOption(
            self::OPTION_REMOVE_SINGLY_IMPLEMENTED,
            'r',
            InputOption::VALUE_NONE,
            sprintf(
                'Remove aliases added only for only-implementations, useful in combination with "%s".',
                AutowireSinglyImplementedCompilerPass::class
            )
        );

        $this->addOption(
            self::OPTION_NESTING_LEVEL,
            'l',
            InputOption::VALUE_REQUIRED,
            'How many namespace levels should be separated in autodiscovery, e.g 1 → "App\", 2 → "App\SomeProject\"',
            2
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $servicesFile = $input->getArgument('file');
        $removeSinglyImplemented = (bool) $input->getOption(self::OPTION_REMOVE_SINGLY_IMPLEMENTED);
        $nestingLevel = (int) $input->getOption(self::OPTION_NESTING_LEVEL);

        $this->symfonyStyle->note('Processing ' . realpath($servicesFile));
        $this->symfonyStyle->newLine();

        $servicesContent = FileSystem::read($servicesFile);
        $servicesYaml = Yaml::parse($servicesContent);

        $convertedYaml = $this->explicitToAutodiscoveryConverter->convert(
            $servicesYaml,
            $servicesFile,
            $nestingLevel,
            $removeSinglyImplemented
        );

        $convertedContent = Yaml::dump($convertedYaml, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        $this->symfonyStyle->writeln($convertedContent);

        return ShellCode::SUCCESS;
    }
}