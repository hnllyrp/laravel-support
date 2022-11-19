<?php

namespace Hnllyrp\LaravelSupport\Support\Commands\Model;

use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\Filesystem;
use Reliese\Coders\Model\Config;
use Reliese\Coders\Model\Model;
use Reliese\Support\Classify;

class Factory extends \Reliese\Coders\Model\Factory
{
    /**
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $db;

    public function __construct(DatabaseManager $db, Filesystem $files, Classify $writer, Config $config)
    {
        parent::__construct($db, $files, $writer, $config);

        $this->db = $db;
        $this->files = $files;
        $this->config = $config;
        $this->class = $writer;
    }

    /**
     * @param string $schema
     * @param string $table
     */
    public function create($schema, $table)
    {
        $model = $this->makeModel($schema, $table);
        $template = $this->prepareTemplate($model, 'model');

        $file = $this->fillTemplate($template, $model);

        if ($model->indentWithSpace()) {
            $file = str_replace("\t", str_repeat(' ', $model->indentWithSpace()), $file);
        }

        // 自定义Base目录
        $customBasePath = $this->config($model->getBlueprint(), 'base_files_path', 'Base');

        $this->files->put($this->modelPath($model, $model->usesBaseFiles() ? [$customBasePath] : []), $file);

        if ($this->needsUserFile($model)) {
            $this->createUserFile($model);
        }
    }

    /**
     * @param string $template
     * @param \Reliese\Coders\Model\Model $model
     *
     * @return mixed
     */
    protected function fillTemplate($template, Model $model)
    {
        // 自定义Base目录
        $customBasePath = $this->config($model->getBlueprint(), 'base_files_path', 'Base');

        $template = str_replace('{{namespace}}', $this->getCustomNamespace($model, $customBasePath), $template);
        $template = str_replace('{{class}}', $model->getClassName(), $template);

        $properties = $this->properties($model);
        $dependencies = $this->shortenAndExtractImportableDependencies($properties, $model);
        $template = str_replace('{{properties}}', $properties, $template);

        $parentClass = $model->getParentClass();
        $dependencies = array_merge($dependencies, $this->shortenAndExtractImportableDependencies($parentClass, $model));
        $template = str_replace('{{parent}}', $parentClass, $template);

        $body = $this->body($model);
        $dependencies = array_merge($dependencies, $this->shortenAndExtractImportableDependencies($body, $model));
        $template = str_replace('{{body}}', $body, $template);

        $imports = $this->imports(array_keys($dependencies), $model);
        $template = str_replace('{{imports}}', $imports, $template);

        return $template;
    }

    /**
     * Returns imports section for model.
     *
     * @param array $dependencies Array of imported classes
     * @param Model $model
     * @return string
     */
    protected function imports($dependencies, Model $model)
    {
        $imports = [];
        foreach ($dependencies as $dependencyClass) {
            // Skip when the same class
            if ($dependencyClass == $model->getQualifiedUserClassName()) {
                continue;
            }

            // Do not import classes from same namespace
            $inCurrentNamespacePattern = str_replace('\\', '\\\\', "/{$model->getBaseNamespace()}\\[a-zA-Z0-9_]*/");
            if (preg_match($inCurrentNamespacePattern, $dependencyClass)) {
                continue;
            }

            $imports[] = "use {$dependencyClass};";
        }

        sort($imports);

        return implode("\n", $imports);
    }

    /**
     * Extract and replace fully-qualified class names from placeholder.
     *
     * @param string $placeholder Placeholder to extract class names from. Rewrites value to content without FQN
     * @param \Reliese\Coders\Model\Model $model
     *
     * @return array Extracted FQN
     */
    protected function shortenAndExtractImportableDependencies(&$placeholder, $model)
    {
        $qualifiedClassesPattern = '/([\\\\a-zA-Z0-9_]*\\\\[\\\\a-zA-Z0-9_]*)/';
        $matches = [];
        $importableDependencies = [];
        if (preg_match_all($qualifiedClassesPattern, $placeholder, $matches)) {
            foreach ($matches[1] as $usedClass) {
                $namespacePieces = explode('\\', $usedClass);
                $className = array_pop($namespacePieces);

                //When same class name but different namespace, skip it.
                if (
                    $className == $model->getClassName() &&
                    trim(implode('\\', $namespacePieces), '\\') != trim($model->getNamespace(), '\\')
                ) {
                    continue;
                }

                $importableDependencies[trim($usedClass, '\\')] = true;
                $placeholder = str_replace($usedClass, $className, $placeholder);
            }
        }

        return $importableDependencies;
    }

    /**
     * @param \Reliese\Coders\Model\Model $model
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function createUserFile(Model $model)
    {
        $file = $this->modelPath($model);

        // 自定义Base目录
        $customBasePath = $this->config($model->getBlueprint(), 'base_files_path', 'Base');

        $template = $this->prepareTemplate($model, 'user_model');
        $template = str_replace('{{namespace}}', $model->getNamespace(), $template);
        $template = str_replace('{{class}}', $model->getClassName(), $template);
        $template = str_replace('{{imports}}', $this->formatBaseClasses($model, $customBasePath), $template);
        $template = str_replace('{{parent}}', $this->getBaseClassName($model), $template);
        $template = str_replace('{{body}}', $this->userFileBody($model), $template);

        $this->files->put($file, $template);
    }

    protected function getCustomNamespace(Model $model, $customBasePath = 'Base')
    {
        return $model->usesBaseFiles()
            ? $model->getNamespace() . '\\' . $customBasePath
            : $model->getNamespace();
    }

    /**
     * @param Model $model
     * @return string
     */
    protected function formatBaseClasses(Model $model, $customBasePath = 'Base')
    {
        return "use {$this->getCustomNamespace($model, $customBasePath)}\\{$model->getClassName()} as {$this->getBaseClassName($model)};";
    }

    /**
     * @param Model $model
     * @return string
     */
    protected function getBaseClassName(Model $model)
    {
        // return 'Base' . $model->getClassName();
        return 'Model';
    }

    /**
     * @param \Reliese\Coders\Model\Model $model
     *
     * @return string
     */
    protected function body(Model $model)
    {
        $body = '';

        foreach ($model->getTraits() as $trait) {
            $body .= $this->class->mixin($trait);
        }

        $excludedConstants = [];

        if ($model->hasCustomCreatedAtField()) {
            $body .= $this->class->constant('CREATED_AT', $model->getCreatedAtField());
            $excludedConstants[] = $model->getCreatedAtField();
        }

        if ($model->hasCustomUpdatedAtField()) {
            $body .= $this->class->constant('UPDATED_AT', $model->getUpdatedAtField());
            $excludedConstants[] = $model->getUpdatedAtField();
        }

        if ($model->hasCustomDeletedAtField()) {
            $body .= $this->class->constant('DELETED_AT', $model->getDeletedAtField());
            $excludedConstants[] = $model->getDeletedAtField();
        }

        if ($model->usesPropertyConstants()) {
            // Take all properties and exclude already added constants with timestamps.
            $properties = array_keys($model->getProperties());
            $properties = array_diff($properties, $excludedConstants);

            foreach ($properties as $property) {
                $body .= $this->class->constant(strtoupper($property), $property);
            }
        }

        $body = trim($body, "\n");
        // Separate constants from fields only if there are constants.
        if (!empty($body)) {
            $body .= "\n";
        }

        // Append connection name when required
        if ($model->shouldShowConnection()) {
            $body .= $this->class->field('connection', $model->getConnectionName());
        }

        // When table is not plural, append the table name
        if ($model->needsTableName()) {
            // $body .= $this->class->field('table', $model->getTableForQuery());
            $body .= $this->class->field('table', $model->getTable(true));
        }

        if ($model->hasCustomPrimaryKey()) {
            $body .= $this->class->field('primaryKey', $model->getPrimaryKey());
        }

        if ($model->doesNotAutoincrement()) {
            $body .= $this->class->field('incrementing', false, ['visibility' => 'public']);
        }

        if ($model->hasCustomPerPage()) {
            $body .= $this->class->field('perPage', $model->getPerPage());
        }

        if (!$model->usesTimestamps()) {
            $body .= $this->class->field('timestamps', false, ['visibility' => 'public']);
        }

        if ($model->hasCustomDateFormat()) {
            $body .= $this->class->field('dateFormat', $model->getDateFormat());
        }

        if ($model->doesNotUseSnakeAttributes()) {
            $body .= $this->class->field('snakeAttributes', false, ['visibility' => 'public static']);
        }

        if ($model->hasCasts()) {
            $body .= $this->class->field('casts', $model->getCasts(), ['before' => "\n"]);
        }

        if ($model->hasDates()) {
            $body .= $this->class->field('dates', $model->getDates(), ['before' => "\n"]);
        }

        // if ($model->hasHidden() && $model->doesNotUseBaseFiles()) {
        //     $body .= $this->class->field('hidden', $model->getHidden(), ['before' => "\n"]);
        // }
        //
        // if ($model->hasFillable() && $model->doesNotUseBaseFiles()) {
        //     $body .= $this->class->field('fillable', $model->getFillable(), ['before' => "\n"]);
        // }

        if ($model->hasHidden()) {
            $body .= $this->class->field('hidden', $model->getHidden(), ['before' => "\n"]);
        }

        if ($model->hasFillable()) {
            $body .= $this->class->field('fillable', $model->getFillable(), ['before' => "\n"]);
        }

        if ($model->hasHints() && $model->usesHints()) {
            $body .= $this->class->field('hints', $model->getHints(), ['before' => "\n"]);
        }

        foreach ($model->getMutations() as $mutation) {
            $body .= $this->class->method($mutation->name(), $mutation->body(), ['before' => "\n"]);
        }

        foreach ($model->getRelations() as $constraint) {
            $body .= $this->class->method($constraint->name(), $constraint->body(), ['before' => "\n"]);
        }

        // Make sure there not undesired line breaks
        $body = trim($body, "\n");

        return $body;
    }

    /**
     * @param \Reliese\Coders\Model\Model $model
     *
     * @return string
     */
    protected function userFileBody(Model $model)
    {
        $body = '';

        // if ($model->hasHidden()) {
        //     $body .= $this->class->field('hidden', $model->getHidden());
        // }
        //
        // if ($model->hasFillable()) {
        //     $body .= $this->class->field('fillable', $model->getFillable(), ['before' => "\n"]);
        // }

        // Make sure there is not an undesired line break at the end of the class body
        $body = ltrim(rtrim($body, "\n"), "\n");

        return $body;
    }
}
