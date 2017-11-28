<?php declare(strict_types=1);

namespace Symplify\CodingStandard\Fixer\Commenting;

use Nette\Utils\Strings;
use PhpCsFixer\Fixer\DefinedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;
use Symplify\TokenRunner\Wrapper\FixerWrapper\ClassWrapper;
use Symplify\TokenRunner\Wrapper\FixerWrapper\DocBlockWrapper;
use Symplify\TokenRunner\Wrapper\FixerWrapper\MethodWrapper;

final class RemoveUselessDocBlockFixer implements FixerInterface, DefinedFixerInterface
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Block comment should only contain useful information about types.',
            [
                new CodeSample('<?php
/**
 * @return int 
 */
public function getCount(): int
{
}
'),
            ]
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAllTokenKindsFound([T_FUNCTION, T_DOC_COMMENT]);
    }

    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = count($tokens) - 1; $index > 1; --$index) {
            $token = $tokens[$index];

            if (! $token->isGivenKind(T_CLASS)) {
                continue;
            }

            $classWrapper = ClassWrapper::createFromTokensArrayStartPosition($tokens, $index);
            foreach ($classWrapper->getMethodWrappers() as $methodWrapper) {
                $docBlockWrapper = $methodWrapper->getDocBlockWrapper();
                if ($docBlockWrapper === null) {
                    continue;
                }

                $this->processReturnTag($methodWrapper, $docBlockWrapper);
                $this->processParamTag($methodWrapper, $docBlockWrapper);
            }
        }
    }

    public function isRisky(): bool
    {
        return false;
    }

    public function getName(): string
    {
        return self::class;
    }

    /**
     * Runs before @see \PhpCsFixer\Fixer\Phpdoc\NoEmptyPhpdocFixer.
     */
    public function getPriority(): int
    {
        return 10;
    }

    public function supports(SplFileInfo $file): bool
    {
        return true;
    }

    private function processReturnTag(MethodWrapper $methodWrapper, DocBlockWrapper $docBlockWrapper): void
    {
        $typehintType = $methodWrapper->getReturnType();
        $docBlockType = $docBlockWrapper->getReturnType();

        if ($typehintType === $docBlockType) {
            if ($docBlockWrapper->getReturnTypeDescription()) {
                return;
            }

            $docBlockWrapper->removeReturnType();
        }

        if ($typehintType === null || $docBlockType === null) {
            return;
        }

        if (Strings::contains($typehintType, '|') && Strings::contains($docBlockType, '|')) {
            $this->processReturnTagMultiTypes($typehintType, $docBlockType, $docBlockWrapper);
        }

        if ($typehintType && Strings::endsWith($typehintType, '\\' . $docBlockWrapper->getReturnType())) {
            $docBlockWrapper->removeReturnType();
            return;
        }

        // simple types
        if ($docBlockType === 'boolean' && $typehintType === 'bool') {
            $docBlockWrapper->removeReturnType();
        }
    }

    private function processParamTag(MethodWrapper $methodWrapper, DocBlockWrapper $docBlockWrapper): void
    {
        foreach ($methodWrapper->getArguments() as $argumentWrapper) {
            $argumentType = $docBlockWrapper->getArgumentType($argumentWrapper->getName());
            $argumentDescription = $docBlockWrapper->getArgumentTypeDescription($argumentWrapper->getName());

            if ($argumentType === $argumentDescription) {
                $docBlockWrapper->removeParamType($argumentWrapper->getName());

                continue;
            }

            if ($argumentDescription === null || $argumentType === null) {
                continue;
            }

            $isDescriptionUseful = $this->isDescriptionUseful(
                $argumentDescription,
                $argumentType,
                $argumentWrapper->getName()
            );

            if ($argumentType === $argumentWrapper->getType()) {
                if ($argumentDescription && $isDescriptionUseful) {
                    continue;
                }

                $docBlockWrapper->removeParamType($argumentWrapper->getName());
                continue;
            }

            if ($argumentType && Strings::endsWith($argumentType, '\\' . $argumentWrapper->getType())) {
                if ($isDescriptionUseful) {
                    continue;
                }

                $docBlockWrapper->removeParamType($argumentWrapper->getName());
                return;
            }

            // simple types
            if ($argumentType === 'boolean' && $argumentWrapper->getType() === 'bool') {
                $docBlockWrapper->removeParamType($argumentWrapper->getName());
            }
        }
    }

    private function isDescriptionUseful(string $description, ?string $type, ?string $name): bool
    {
        if (! $description || $type === null) {
            return false;
        }

        if (Strings::endsWith($type, 'Interface')) {
            // SomeTypeInterface => TypeInterface
            $type = substr($type, 0, -strlen('Interface'));
        }

        if (Strings::endsWith($type, '[]')) {
            return true;
        }

        $isDummyDescription = (bool) Strings::match(
            $description,
            sprintf('#^(A|An|The|the) (\\\\)?%s(Interface)?( instance)?$#i', $type)
        ) || levenshtein($type, $description) < 2;

        // improve with additional cases, probably regex
        if ($type && $isDummyDescription) {
            return false;
        }

        if (levenshtein($name, $description) < 2) {
            return false;
        }

        return true;
    }

    private function processReturnTagMultiTypes(
        string $docBlockType,
        string $typehintType,
        DocBlockWrapper $docBlockWrapper
    ): void {
        $typehintTypes = explode('|', $typehintType);
        $docBlockTypes = explode('|', $docBlockType);

        if ($docBlockWrapper->getReturnTypeDescription()) {
            return;
        }

        sort($typehintTypes);
        sort($docBlockTypes);

        if ($typehintTypes === $docBlockTypes) {
            $docBlockWrapper->removeReturnType();
        }
    }
}
