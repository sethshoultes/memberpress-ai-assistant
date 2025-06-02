<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

use function range;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

abstract class FileAnalyserTestCase extends TestCase
{
    public function testGetLinesToBeIgnored(): void
    {
        $this->assertSame(
            [
                3,
                4,
                5,
                11,
                12,
                13,
                14,
                15,
                16,
                18,
                23,
                24,
                25,
                30,
                33,
                38,
                39,
                40,
                41,
                42,
            ],
            $this->analyser()->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_ignore.php',
            ),
        );
    }

    public function testGetLinesToBeIgnored2(): void
    {
        $this->assertSame(
            [],
            $this->analyser()->ignoredLinesFor(
                TEST_FILES_PATH . 'source_without_ignore.php',
            ),
        );
    }

    public function testGetLinesToBeIgnored3(): void
    {
        $this->assertSame(
            [
                3,
            ],
            $this->analyser()->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php',
            ),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/793')]
    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/794')]
    public function testLineWithFullyQualifiedClassNameConstantIsNotIgnored(): void
    {
        $this->assertSame(
            [
                2,
            ],
            $this->analyser()->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_class_and_fqcn_constant.php',
            ),
        );
    }

    public function testGetLinesToBeIgnoredOneLineAnnotations(): void
    {
        $this->assertSame(
            [
                4,
                9,
                29,
                31,
                32,
                33,
            ],
            $this->analyser()->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_oneline_annotations.php',
            ),
        );
    }

    public function testGetLinesToBeIgnoredWhenIgnoreIsDisabled(): void
    {
        $this->assertSame(
            [
                11,
                18,
                33,
            ],
            (new ParsingFileAnalyser(false, false))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_ignore.php',
            ),
        );
    }

    public function testGetLinesOfCodeForFileWithoutNewline(): void
    {
        $this->assertSame(
            1,
            (new ParsingFileAnalyser(false, false))->linesOfCodeFor(
                TEST_FILES_PATH . 'source_without_newline.php',
            )->linesOfCode(),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/885')]
    public function testGetLinesOfCodeForFileCrLineEndings(): void
    {
        $result = (new ParsingFileAnalyser(false, false))->linesOfCodeFor(
            TEST_FILES_PATH . 'source_without_lf_only_cr.php',
        );

        $this->assertSame(4, $result->linesOfCode());
        $this->assertSame(2, $result->commentLinesOfCode());
        $this->assertSame(2, $result->nonCommentLinesOfCode());
    }

    public function testLinesCanBeIgnoredUsingAttribute(): void
    {
        $this->assertSame(
            [
                4,
                5,
                6,
                7,
                8,
                9,
                10,
                11,
                13,
                15,
                16,
                17,
                18,
                19,
            ],
            $this->analyser()->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_ignore_attributes.php',
            ),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1033')]
    public function testEnumWithEnumLevelIgnore(): void
    {
        $this->assertSame(
            range(5, 13),
            $this->analyser()->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_enum_and_enum_level_ignore_annotation.php',
            ),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1033')]
    public function testEnumWithMethodLevelIgnore(): void
    {
        $this->assertSame(
            range(9, 12),
            $this->analyser()->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_enum_and_method_level_ignore_annotation.php',
            ),
        );
    }

    public function testCodeUnitsAreFound(): void
    {
        $analyser = new ParsingFileAnalyser(true, true);
        $file     = __DIR__ . '/../_files/source_with_interfaces_classes_traits_functions.php';

        $interfaces = $analyser->interfacesIn($file);

        $this->assertCount(3, $interfaces);
        $this->assertArrayHasKey('SebastianBergmann\CodeCoverage\StaticAnalysis\A', $interfaces);
        $this->assertArrayHasKey('SebastianBergmann\CodeCoverage\StaticAnalysis\B', $interfaces);
        $this->assertArrayHasKey('SebastianBergmann\CodeCoverage\StaticAnalysis\C', $interfaces);

        $classes = $analyser->classesIn($file);

        $this->assertCount(2, $classes);
        $this->assertArrayHasKey('SebastianBergmann\CodeCoverage\StaticAnalysis\ParentClass', $classes);
        $this->assertArrayHasKey('SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass', $classes);

        $traits = $analyser->traitsIn($file);

        $this->assertCount(1, $traits);
        $this->assertArrayHasKey('SebastianBergmann\CodeCoverage\StaticAnalysis\T', $traits);

        $functions = $analyser->functionsIn($file);

        $this->assertCount(1, $functions);
        $this->assertArrayHasKey('SebastianBergmann\CodeCoverage\StaticAnalysis\f', $functions);
    }

    abstract protected function analyser(): FileAnalyser;
}
