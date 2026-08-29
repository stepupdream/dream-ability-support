<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Test\Supports\CodeGeneration;

use LogicException;
use StepUpDream\DreamAbilitySupport\Supports\CodeGeneration\PreservedBlockMerger;

beforeEach(function () {
    $this->preservedBlockMerger = new PreservedBlockMerger();
    $this->generated = <<<'PHP_CODE'
        <?php

        class Sample
        {
            // preserve:begin(trait)
            // preserve:end(trait)

            public function generated(): void
            {
            }

            // preserve:begin(method)
            // preserve:end(method)
        }

        PHP_CODE;
});

describe('merge', function () {
    it('takes over every preserved block of the existing file', function () {
        $current = <<<'PHP_CODE'
            <?php

            class Sample
            {
                // preserve:begin(trait)
                use SampleTrait;
                // preserve:end(trait)

                public function old(): void
                {
                }

                // preserve:begin(method)
                public function handWritten(): void
                {
                }
                // preserve:end(method)
            }

            PHP_CODE;

        $merged = $this->preservedBlockMerger->merge($this->generated, $current);

        expect($merged)->toContain('use SampleTrait;')
            ->and($merged)->toContain('public function handWritten(): void')
            ->and($merged)->toContain('public function generated(): void')
            ->and($merged)->not->toContain('public function old(): void');
    });

    it('returns the same content when it is merged twice', function () {
        $current = str_replace(
            '    // preserve:end(method)',
            "    public function handWritten(): void\n    {\n    }\n    // preserve:end(method)",
            $this->generated
        );

        $merged = $this->preservedBlockMerger->merge($this->generated, $current);

        expect($this->preservedBlockMerger->merge($this->generated, $merged))->toBe($merged);
    });

    it('returns the generated content as it is when the existing file has no preserved block', function () {
        expect($this->preservedBlockMerger->merge($this->generated, "<?php\n"))->toBe($this->generated);
    });

    it('keeps the line ending of CRLF', function () {
        $current = str_replace("\n", "\r\n", str_replace(
            '    // preserve:end(method)',
            "    public function handWritten(): void\n    {\n    }\n    // preserve:end(method)",
            $this->generated
        ));

        $merged = $this->preservedBlockMerger->merge(str_replace("\n", "\r\n", $this->generated), $current);

        expect($merged)->toBe($current);
    });

    it('keeps a multibyte body', function () {
        $generated = "// preserve:begin(comment)\n// preserve:end(comment)\n";
        $current = "// preserve:begin(comment)\n// 日本語のコメント\n// preserve:end(comment)\n";

        expect($this->preservedBlockMerger->merge($generated, $current))->toBe($current);
    });

    it('throws when the generated content lost a preserved block of the existing file', function () {
        $current = "// preserve:begin(method)\npublic function handWritten(): void {}\n// preserve:end(method)\n";

        expect(fn () => $this->preservedBlockMerger->merge("<?php\n", $current))
            ->toThrow(LogicException::class, 'The preserved block does not exist in the generated content: method');
    });

    it('drops a lost preserved block when it is allowed', function () {
        $preservedBlockMerger = new PreservedBlockMerger(throwOnMissingBlock: false);
        $current = "// preserve:begin(method)\npublic function handWritten(): void {}\n// preserve:end(method)\n";

        expect($preservedBlockMerger->merge("<?php\n", $current))->toBe("<?php\n");
    });

    it('can use its own keywords', function () {
        $preservedBlockMerger = new PreservedBlockMerger('BEGIN CUSTOM CODE', 'END CUSTOM CODE');
        $generated = "# BEGIN CUSTOM CODE(setting)\n# END CUSTOM CODE(setting)\n";
        $current = "# BEGIN CUSTOM CODE(setting)\ndebug: true\n# END CUSTOM CODE(setting)\n";

        expect($preservedBlockMerger->merge($generated, $current))->toBe($current);
    });
});

describe('blocks', function () {
    it('returns the body of every preserved block', function () {
        $content = "// preserve:begin(a)\nfirst\nsecond\n// preserve:end(a)\n// preserve:begin(b)\n// preserve:end(b)\n";

        expect($this->preservedBlockMerger->blocks($content))->toBe(['a' => ['first', 'second'], 'b' => []]);
    });

    it('throws when the same id is used twice', function () {
        $content = "// preserve:begin(a)\n// preserve:end(a)\n// preserve:begin(a)\n// preserve:end(a)\n";

        expect(fn () => $this->preservedBlockMerger->blocks($content))
            ->toThrow(LogicException::class, 'The id of the preserved block is duplicated: a');
    });

    it('throws when a preserved block is not closed', function () {
        expect(fn () => $this->preservedBlockMerger->blocks("// preserve:begin(a)\n"))
            ->toThrow(LogicException::class, 'The preserved block is not closed: a');
    });

    it('throws when a preserved block is closed by a different id', function () {
        expect(fn () => $this->preservedBlockMerger->blocks("// preserve:begin(a)\n// preserve:end(b)\n"))
            ->toThrow(LogicException::class, 'The preserved block is closed by a different id: a, b');
    });

    it('throws when a preserved block is not opened', function () {
        expect(fn () => $this->preservedBlockMerger->blocks("// preserve:end(a)\n"))
            ->toThrow(LogicException::class, 'The preserved block is not opened: a');
    });

    it('throws when the id is empty', function () {
        expect(fn () => $this->preservedBlockMerger->blocks("// preserve:begin()\n"))
            ->toThrow(LogicException::class, 'The id of the preserved block is required: // preserve:begin()');
    });
});

describe('hasBlock', function () {
    it('tells whether the content declares a preserved block', function () {
        expect($this->preservedBlockMerger->hasBlock($this->generated))->toBeTrue()
            ->and($this->preservedBlockMerger->hasBlock("<?php\n"))->toBeFalse();
    });
});
