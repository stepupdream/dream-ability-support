<?php

declare(strict_types=1);

namespace StepUpDream\DreamAbilitySupport\Console;

use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Info;
use LogicException;

abstract class BaseCommand extends Command
{
    /**
     * Create a new console command instance.
     */
    public function __construct()
    {
        ini_set('memory_limit', '2056M');

        parent::__construct();
    }

    /**
     * Command execution log.
     *
     * @param  string  $description
     * @return void
     */
    public function commandDetailLog(string $description = 'Command run detail'): void
    {
        (new Info($this->output))->render($description);
        $runTime = number_format((microtime(true) - LARAVEL_START) * 1000).'ms';
        $usedMemory = sprintf('%sMB', memory_get_peak_usage(true) / 1024 / 1024);
        $this->line(sprintf("  run_time : %s\n  used_memory : %s\n", $runTime, $usedMemory));
    }

    /**
     * Whether it is a multidimensional array.
     *
     * @param  mixed[]  $array
     * @return bool
     */
    public function isMultidimensional(array $array): bool
    {
        return count($array) !== count($array, 1);
    }

    /**
     * Command option text.
     *
     * @param  string  $optionKey
     * @return string|null
     */
    protected function optionText(string $optionKey): string|null
    {
        $option = $this->option($optionKey);
        if ($option === null) {
            return null;
        }

        if (is_string($option)) {
            return $option;
        }

        throw new LogicException('The option specification is incorrect: '.$optionKey);
    }
}
