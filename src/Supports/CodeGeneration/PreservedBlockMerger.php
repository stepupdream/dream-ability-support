<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Supports\CodeGeneration;

use LogicException;

/**
 * PreservedBlockMerger class.
 *
 * Merge the hand-written parts of an already generated file into a newly generated one.
 *
 * A preserved block is the range between a begin marker line and an end marker line.
 * Each block is identified by an id, so any number of blocks can be placed in a file
 * and they are matched by id instead of by their position.
 *
 * ```
 * // preserve:begin(imports)
 * use App\Foo;
 * // preserve:end(imports)
 * ```
 *
 * The marker lines themselves are always taken from the newly generated content:
 * only the lines between them are kept from the existing file.
 */
class PreservedBlockMerger
{
    /**
     * @param  string  $beginKeyword  Keyword that opens a preserved block. The id follows it in parentheses.
     * @param  string  $endKeyword  Keyword that closes a preserved block. The id follows it in parentheses.
     * @param  bool  $throwOnMissingBlock  Throw when the existing file has a block that the new content does not
     *                                     declare. Such a block would be dropped without notice otherwise.
     */
    public function __construct(
        protected string $beginKeyword = 'preserve:begin',
        protected string $endKeyword = 'preserve:end',
        protected bool $throwOnMissingBlock = true,
    ) {
    }

    /**
     * Rebuild the newly generated content with the preserved blocks of the current content.
     *
     * @param  string  $newContent  Newly generated content.
     * @param  string  $currentContent  Content of the file that already exists.
     */
    public function merge(string $newContent, string $currentContent): string
    {
        $currentBlocks = $this->blocks($currentContent);

        if ($currentBlocks === []) {
            return $newContent;
        }

        $newLines = $this->lines($newContent);
        $newRanges = $this->parse($newLines);

        if ($this->throwOnMissingBlock) {
            $missingIds = array_diff(array_keys($currentBlocks), array_keys($newRanges));
            if ($missingIds !== []) {
                throw new LogicException(
                    'The preserved block does not exist in the generated content: ' . implode(', ', $missingIds)
                );
            }
        }

        $mergedLines = [];
        $skipUntil = null;

        foreach ($newLines as $index => $line) {
            if ($skipUntil !== null) {
                if ($index < $skipUntil) {
                    continue;
                }

                $skipUntil = null;
            }

            $mergedLines[] = $line;

            $id = $this->blockIdOfBeginLine($line);
            if ($id === null || !array_key_exists($id, $currentBlocks)) {
                continue;
            }

            foreach ($currentBlocks[$id] as $bodyLine) {
                $mergedLines[] = $bodyLine;
            }

            $skipUntil = $newRanges[$id]['end'];
        }

        return implode("\n", $mergedLines);
    }

    /**
     * Whether the content declares at least one preserved block.
     */
    public function hasBlock(string $content): bool
    {
        return $this->parse($this->lines($content)) !== [];
    }

    /**
     * Get the body of every preserved block of the content, keyed by its id.
     *
     * @return array<string, string[]>
     */
    public function blocks(string $content): array
    {
        $lines = $this->lines($content);
        $blocks = [];

        foreach ($this->parse($lines) as $id => $range) {
            $blocks[$id] = array_slice($lines, $range['start'], $range['end'] - $range['start']);
        }

        return $blocks;
    }

    /**
     * Get the body line range of every preserved block, keyed by its id.
     *
     * The range is the line index of the first body line and the line index of the end marker.
     *
     * @param  string[]  $lines
     * @return array<string, array{start: int, end: int}>
     */
    protected function parse(array $lines): array
    {
        $ranges = [];
        $openId = null;
        $bodyStart = 0;

        foreach ($lines as $index => $line) {
            $beginId = $this->blockIdOfBeginLine($line);
            $endId = $this->blockIdOfEndLine($line);

            if ($beginId !== null) {
                if ($openId !== null) {
                    throw new LogicException("The preserved block is not closed: $openId");
                }

                if (array_key_exists($beginId, $ranges)) {
                    throw new LogicException("The id of the preserved block is duplicated: $beginId");
                }

                $openId = $beginId;
                $bodyStart = $index + 1;
                continue;
            }

            if ($endId === null) {
                continue;
            }

            if ($openId === null) {
                throw new LogicException("The preserved block is not opened: $endId");
            }

            if ($openId !== $endId) {
                throw new LogicException("The preserved block is closed by a different id: $openId, $endId");
            }

            $ranges[$endId] = ['start' => $bodyStart, 'end' => $index];
            $openId = null;
        }

        if ($openId !== null) {
            throw new LogicException("The preserved block is not closed: $openId");
        }

        return $ranges;
    }

    /**
     * Get the block id of the line if it is a begin marker.
     */
    protected function blockIdOfBeginLine(string $line): ?string
    {
        return $this->blockId($line, $this->beginKeyword);
    }

    /**
     * Get the block id of the line if it is an end marker.
     */
    protected function blockIdOfEndLine(string $line): ?string
    {
        return $this->blockId($line, $this->endKeyword);
    }

    /**
     * Get the block id that the keyword of the line declares.
     */
    protected function blockId(string $line, string $keyword): ?string
    {
        $pattern = '/' . preg_quote($keyword, '/') . '\(\s*(?<id>[^()]*?)\s*\)/';
        $matches = [];

        if (preg_match($pattern, $line, $matches) !== 1) {
            return null;
        }

        if ($matches['id'] === '') {
            throw new LogicException('The id of the preserved block is required: ' . trim($line));
        }

        return $matches['id'];
    }

    /**
     * Split the content into lines. A carriage return is kept as a part of the line.
     *
     * @return string[]
     */
    protected function lines(string $content): array
    {
        return explode("\n", $content);
    }
}
