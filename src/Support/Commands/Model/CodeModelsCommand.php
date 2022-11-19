<?php

namespace Hnllyrp\LaravelSupport\Support\Commands\Model;

use Illuminate\Contracts\Config\Repository;
use Hnllyrp\LaravelSupport\Support\Commands\Model\Factory;
use Reliese\Coders\Console\CodeModelsCommand as Command;

class CodeModelsCommand extends Command
{

    /**
     * Create a new command instance.
     *
     * @param \Hnllyrp\LaravelSupport\Support\Commands\Model\Factory $models
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Factory $models, Repository $config)
    {
        parent::__construct($models, $config);

        $this->models = $models;
        $this->config = $config;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = $this->getConnection();
        $schema = $this->getSchema($connection);
        $table = $this->getTable();

        // Check whether we just need to generate one table
        if ($table) {
            $this->models->on($connection)->create($schema, $table);
            $this->info("Check out your models for $table");
        } // Otherwise map the whole database
        else {
            $this->models->on($connection)->map($schema);
            $this->info("Check out your models for $schema");
        }
    }

}
