<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Console\View\Components;

use Illuminate\Console\View\Components\Component;
use Illuminate\Console\View\Components\Mutators\EnsureDynamicContentIsHighlighted;
use Illuminate\Console\View\Components\Mutators\EnsureNoPunctuation;
use Illuminate\Console\View\Components\Mutators\EnsureRelativePaths;
use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\terminal;

/**
 * A console component for displaying task execution status and progress.
 *
 * This class is based on the console output, task description, execution time, and
 * and execution results (such as DONE, SKIP, FAIL, ERROR).
 * Provides visually shaping and displaying functions.
 */
class Task extends Component
{
    /**
     * Maximum width (character number) of console output
     */
    private const int MAX_WIDTH = 150;

    /**
     * Minimum interval for dot display (number of characters)
     */
    private const int MIN_DOT_SPACING = 10;

    /**
     * Renders the component using the given arguments.
     */
    public function render(
        string $description,
        ?string $task = null,
        int $verbosity = OutputInterface::VERBOSITY_NORMAL,
    ): void {
        $description = $this->applyMutators($description);
        $descriptionWidth = mb_strlen($description);

        $this->output->write("  $description ", false, $verbosity);

        $startTime = microtime(true);
        $result = $task ?: 'DONE';
        $runTime = ' ' . number_format((microtime(true) - $startTime) * 1000) . 'ms';
        $this->writeDotsAndTime($descriptionWidth, $runTime, $verbosity);
        $this->writeResult($result, $verbosity);
    }

    /**
     * Applies the defined mutators to the description.
     */
    private function applyMutators(string $description): string
    {
        $mutators = [
            EnsureDynamicContentIsHighlighted::class,
            EnsureNoPunctuation::class,
            EnsureRelativePaths::class,
        ];

        foreach ($mutators as $mutator) {
            $description = (new $mutator())->__invoke($description);
        }

        return $description;
    }

    /**
     * Writes dots and runtime to the output.
     */
    private function writeDotsAndTime(int $descriptionWidth, string $runTime, int $verbosity): void
    {
        $runTimeWidth = mb_strlen($runTime);
        $width = min(terminal()->width(), self::MAX_WIDTH);
        $dots = max($width - $descriptionWidth - $runTimeWidth - self::MIN_DOT_SPACING, 0);

        $this->output->write(str_repeat('<fg=gray>.</>', $dots), false, $verbosity);
        $this->output->write("<fg=gray>$runTime</>", false, $verbosity);
    }

    /**
     * Writes the result (status) to the output.
     */
    private function writeResult(string $result, int $verbosity): void
    {
        $statusColors = [
            'SKIP' => '<fg=cyan;options=bold>SKIP</>',
            'DONE' => '<fg=green;options=bold>DONE</>',
            'FAIL' => '<fg=yellow;options=bold>FAIL</>',
            'ERROR' => '<fg=red;options=bold>ERROR</>',
        ];

        $statusMessage = $statusColors[$result] ?? $statusColors['ERROR'];
        $this->output->writeln(" $statusMessage", $verbosity);
    }
}
