<?php

namespace Hnllyrp\LaravelSupport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/**
 * 查看 app 开头的 所有命令行
 * Class AppCommand
 */
class AppCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List app all commands';

    /**
     * 点阵字母
     * http://patorjk.com/software/taag/#p=display&f=Small%20Slant&t=app
     * @var string
     */
    public static $logo = <<<LOGO

 ___ ____  ___
/ _ `/ _ \/ _ \
\_,_/ .__/ .__/
   /_/  /_/

LOGO;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->line(static::$logo);
        $this->line(self::getVersion());

        $this->comment('');
        $this->comment('Available commands:');

        $this->listCommands();
    }

    /**
     * getVersion
     * @return string
     */
    public static function getVersion()
    {
        // return VERSION . ' ' .  RELEASE;
    }

    /**
     * List all commands.
     *
     * @return void
     */
    protected function listCommands()
    {
        $commands = collect(Artisan::all())->mapWithKeys(function ($command, $key) {
            if (Str::startsWith($key, 'app:')) {
                return [$key => $command];
            }

            return [];
        })->toArray();

        $width = $this->getColumnWidth($commands);

        /** @var Command $command */
        foreach ($commands as $command) {
            $this->line(sprintf(" %-{$width}s %s", $command->getName(), $command->getDescription()));
        }
    }

    /**
     * @param (Command|string)[] $commands
     *
     * @return int
     */
    private function getColumnWidth(array $commands)
    {
        $widths = [];

        foreach ($commands as $command) {
            $widths[] = static::strlen($command->getName());
            foreach ($command->getAliases() as $alias) {
                $widths[] = static::strlen($alias);
            }
        }

        return $widths ? max($widths) + 2 : 0;
    }

    /**
     * Returns the length of a string, using mb_strwidth if it is available.
     *
     * @param string $string The string to check its length
     *
     * @return int The length of the string
     */
    public static function strlen($string)
    {
        if (false === $encoding = mb_detect_encoding($string, null, true)) {
            return strlen($string);
        }

        return mb_strwidth($string, $encoding);
    }
}
