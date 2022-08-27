<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Console\View\Components;

use Illuminate\Console\View\Components\Component;
use Illuminate\Console\View\Components\Mutators\EnsureDynamicContentIsHighlighted;
use Illuminate\Console\View\Components\Mutators\EnsureNoPunctuation;
use Illuminate\Console\View\Components\Mutators\EnsureRelativePaths;
use Symfony\Component\Console\Output\OutputInterface;
use function Termwind\terminal;

class Task extends Component
{
    /**
     * Renders the component using the given arguments.
     *
     * I wanted to display SKIP, so I prepared it myself.
     *
     * @param  string  $description
     * @param  callable|null  $task
     * @param  int  $verbosity
     * @return void
     *
     * @see \Illuminate\Console\View\Components\Task::render
     */
    public function render(
        string $description,
        callable $task = null,
        int $verbosity = OutputInterface::VERBOSITY_NORMAL
    ): void {
        // Hack:
        // If you use this->mutate() at this class,
        // a type error will occur in static analysis, so I prepared it myself.
        $mutators = [
            EnsureDynamicContentIsHighlighted::class,
            EnsureNoPunctuation::class,
            EnsureRelativePaths::class,
        ];
        foreach ($mutators as $mutator) {
            $description = app($mutator)->__invoke($description);
        }

        $descriptionWidth = strlen($description);
        $this->output->write("  $description ", false, $verbosity);
        $startTime = microtime(true);
        $result = '';

        try {
            $result = ($task ?: fn () => 'DONE')();
        } finally {
            $runTime = $task ? (' '.number_format((microtime(true) - $startTime) * 1000).'ms') : '';
            $runTimeWidth = strlen($runTime);
            $width = min(terminal()->width(), 150);
            $dots = max($width - $descriptionWidth - $runTimeWidth - 10, 0);
            $this->output->write(str_repeat('<fg=gray>.</>', $dots), false, $verbosity);
            $this->output->write("<fg=gray>$runTime</>", false, $verbosity);

            switch ($result) {
                case 'SKIP':
                    $this->output->writeln(' <fg=cyan;options=bold>SKIP</>', $verbosity);
                    break;
                case 'DONE':
                    $this->output->writeln(' <fg=green;options=bold>DONE</>', $verbosity);
                    break;
                case 'FAIL':
                    $this->output->writeln(' <fg=yellow;options=bold>FAIL</>', $verbosity);
                    break;
                default:
                    $this->output->writeln(' <fg=red;options=bold>ERROR</>', $verbosity);
            }
        }
    }
}
