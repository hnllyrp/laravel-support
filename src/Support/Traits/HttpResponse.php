<?php

namespace Hnllyrp\LaravelSupport\Support\Traits;

use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * Trait HttpResponse
 */
trait HttpResponse
{
    /**
     * 模板输出变量
     *
     * @var array
     */
    protected $tVar = [];

    /**
     * 获取当前模块名
     *
     * @return string
     */
    protected function getCurrentModuleName()
    {
        return $this->getCurrentAction()['module'];
    }

    /**
     * 获取当前控制器名
     *
     * @return string
     */
    protected function getCurrentControllerName()
    {
        return $this->getCurrentAction()['controller'];
    }

    /**
     * 获取当前方法名
     *
     * @return string
     */
    protected function getCurrentMethodName()
    {
        return $this->getCurrentAction()['method'];
    }

    /**
     * 获取当前控制器与方法
     *
     * @return array
     */
    protected function getCurrentAction()
    {
        $action = request()->route()->getAction();

        list($app, $module_path, $module_name) = explode('\\', $action['namespace']);

        $action = str_replace($action['namespace'] . '\\', '', $action['controller']);

        $field = explode('\\', $action);

        if (count($field) > 1) {
            $actions = explode('\\', $action);
            $action = 'Http\\Controllers\\' . $actions[1];
        } else {
            $action = 'Http\\Controllers\\' . $action;
        }

        list($module, $_, $action) = explode('\\', $action);

        list($controller, $action) = explode('@', $action);

        if ($app && $module_path == 'Modules') {
            $module = $module_name; // 获取模块名
        }

        return ['module' => $module, 'controller' => Str::studly($controller), 'method' => $action];
    }

    /**
     * 模板变量赋值
     *
     * @param $name
     * @param string $value
     */
    protected function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->tVar = array_merge($this->tVar, $name);
        } else {
            $this->tVar[$name] = $value;
        }
    }

    /**
     * 加载模板和页面输出 可以返回输出内容
     * @param string $filename
     * @return Factory|View
     */
    protected function display($filename = '')
    {
        if ($filename) {
            return view($filename, $this->tVar);
        }

        $path = strtolower($this->getCurrentModuleName());

        $controller = str_replace('Controller', '', $this->getCurrentControllerName());

        $method = strtolower(Str::camel($this->getCurrentMethodName()));

        $file = $controller . '.' . str_replace('action', '', $method);

        $filename = $path . '.' . strtolower($file);

        return view($filename, $this->tVar);
    }

    /**
     * 异步加载blade模板
     * @param null $tpl
     * @return array|string
     */
    protected function fetch($tpl = null)
    {
        $action = $this->getCurrentAction();

        // 当前主模块
        $module = Str::snake($action['module']);
        // 子模块
        $manage = !empty($action['manage']) ? Str::snake($action['manage']) : '';
        // 控制器
        $controller = str_replace('Controller', '', $action['controller']);
        // 方法名
        $method = str_replace('action', '', $action['method']);

        // 设置视图
        View::addNamespace($module, app_path('Modules') . '/' . $action['module']);

        if (!is_null($tpl)) {
            return view($tpl, $this->tVar)->render();
        }

        // 默认模板
        $tpl = $tpl ? $tpl : Str::snake($controller . '.' . $method);

        if (!empty($manage)) {
            $tpl = $manage . '.' . $tpl;
        }

        return view($module . '::' . $tpl, $this->tVar)->render();
    }

}

