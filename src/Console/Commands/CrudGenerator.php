<?php

namespace Hnllyrp\LaravelSupport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * 生成 CRUD 文件
 * Class CrudGenerator
 * @package Hnllyrp\LaravelSupport\Console\Commands
 */
class CrudGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:generator
        {name : Class (singular) for example User}
        {--type=all : Class type that needs to be generated for example crud:generator Card --type=c|--type=r|--type=m|--type=q\'}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new CRUD operations class';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');

        $type = $this->option('type');

        if ($type === 'all' || $type === 'c') {
            // 生成控制器类 Controller
            $this->controller($name);
        }

        if ($type === 'all' || $type === 'r') {
            // 生成仓储类 Repository
            $this->repository($name);
        }

        if ($type === 'all' || $type === 'm') {
            // 生成模型类 Models
            $this->model($name);
        }

        if ($type === 'all' || $type === 'q') {
            // 生成请求类 Request
            $this->request($name);
        }

        // 往api路由文件中 追加资源路由
        // File::append(base_path('routes/api.php'), 'Route::resource(\'' . str_plural(strtolower($name)) . "', '{$name}Controller');");
    }

    protected function controller($name, $app_path = 'Http')
    {
        $controllerTemplate = str_replace(
            [
                '{{modelName}}',
                '{{modelNamePluralLowerCase}}',
                '{{modelNameSingularLowerCase}}'
            ],
            [
                $name,
                strtolower(Str::plural($name)),
                strtolower($name)
            ],
            $this->getStub('Controller')
        );

        file_put_contents(app_path("$app_path/Controllers/{$name}Controller.php"), $controllerTemplate);
    }

    protected function repository($name)
    {
        $repositoryTemplate = str_replace(
            ['{{modelName}}'],
            [$name],
            $this->getStub('Repository')
        );

        file_put_contents(app_path("Repositories/{$name}Repository.php"), $repositoryTemplate);
    }

    protected function model($name)
    {
        $modelTemplate = str_replace(
            ['{{modelName}}'],
            [$name],
            $this->getStub('Model')
        );

        file_put_contents(app_path("Models/{$name}.php"), $modelTemplate);
    }

    protected function request($name, $app_path = 'Http')
    {
        $requestTemplate = str_replace(
            ['{{modelName}}'],
            [$name],
            $this->getStub('Request')
        );

        if (!file_exists($path = app_path('Http/Requests')))
            mkdir($path, 0777, true);

        file_put_contents(app_path("$app_path/Requests/{$name}Request.php"), $requestTemplate);
    }

    protected function getStub($type)
    {
        return file_get_contents(resource_path("stubs/$type.stub"));
    }

}
