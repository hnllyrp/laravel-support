<?php

namespace Hnllyrp\LaravelSupport\Support\Base;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;
use JsonSerializable;
use Traversable;

/**
 * 一个简单的 DTO 抽象类
 * Class BaseDto
 */
abstract class BaseDto
{
    /**
     * @var array An associative array to store the DTO properties and their values.
     */
    protected $properties = [];

    /**
     * Instantiate the class.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->mergeProperties($data);
        // $this->getProperties();
    }

    /**
     * Set the value of a specific property in the DTO.
     *
     * @param string $propertyName The name of the property.
     * @param mixed $value The value to be set.
     * @return void
     */
    public function setProperty(string $propertyName, $value)
    {
        $this->properties[$propertyName] = $value;
    }

    /**
     * Get the value of a specific property in the DTO.
     *
     * @param string $propertyName The name of the property.
     * @return mixed|null               The value of the property, or null if it doesn't exist.
     */
    public function getProperty(string $propertyName)
    {
        return $this->properties[$propertyName] ?? null;
    }

    /**
     * Check if a specific property exists in the DTO.
     *
     * @param string $propertyName The name of the property.
     * @return bool                 True if the property exists, false otherwise.
     */
    public function hasProperty(string $propertyName)
    {
        return isset($this->properties[$propertyName]);
    }

    /**
     * Remove a specific property from the DTO.
     *
     * @param string $propertyName The name of the property to remove.
     * @return void
     */
    public function removeProperty(string $propertyName)
    {
        if ($this->hasProperty($this->properties[$propertyName])) {
            unset($this->properties[$propertyName]);
        }
    }

    /**
     * Get all properties and their values in the DTO.
     *
     * @return array An associative array containing all the properties and their values.
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Get the names of all properties in the DTO.
     *
     * @return array An array containing the names of all properties.
     */
    public function getPropertyNames()
    {
        return array_keys($this->properties);
    }

    /**
     * Merge an associative array of properties into the current DTO object.
     *
     * @param array $data The associative array of properties.
     * @return void
     */
    public function mergeProperties(array $data)
    {
        foreach ($data as $propertyName => $value) {
            if (is_null($value)) {
                continue;
            }
            $this->setProperty($propertyName, $value);
        }
    }

    /**
     * Clear all properties in the DTO.
     *
     * @return void
     */
    public function clearProperties()
    {
        $this->properties = [];
    }

    /**
     * Clear all properties of the DTO object.
     *
     * @return void
     */
    public function clear()
    {
        $this->clearProperties();
    }

    /**
     * Retrieve an instance of DTO
     *
     * @param array $data
     * @return self
     */
    public static function make(...$parameters)
    {
        return new static(...$parameters);
    }

    /**
     * 显示指定字段
     *
     * @param array $fields
     * @return \Illuminate\Support\Collection
     */
    public function only(array $fields)
    {
        return collect($this->properties)->only($fields);
    }

    /**
     * 隐藏指定字段
     *
     * @param array $fields
     * @return \Illuminate\Support\Collection
     */
    public function except(array $fields)
    {
        return collect($this->properties)->except($fields);
    }

    public function toCollection()
    {
        return collect($this->properties);
    }

    /**
     * Retrieve an instance of DTO from the given or current request
     *
     * @param Request $request The request object to populate the DTO from.
     * @return static
     */
    public static function fromRequest(Request $request)
    {
        $request = $request ?: Request::capture();

        return static::make($request->all());
    }

    /**
     * Convert the DTO to a request object.
     *
     * @return Request The converted request object.
     */
    public function toRequest(): Request
    {
        // Implement logic to convert DTO to request object
        return new Request($this->getProperties());
    }

    /**
     * Retrieve an instance of DTO from the request
     *
     * @param Model $model
     * @return static
     */
    public static function fromModel(Model $model)
    {
        return static::make($model->toArray());
    }

    /**
     * Convert the DTO to a model object.
     *
     * @return Model The converted model object.
     */
    public function toModel(): Model
    {
        $modelClass = $this->getModelClass();
        $model = new $modelClass;

        foreach ($this->getProperties() as $propertyName => $value) {
            if (property_exists($model, $propertyName)) {
                $model->$propertyName = $value;
            }
        }

        return $model;
    }

    /**
     * Get the class name of the model associated with the DTO.
     *
     * @return string The class name of the model.
     */
    protected function getModelClass(): string
    {
        return "User";
    }

    public static function fromArray(array $data): self
    {
        $dto = new static();
        $dto->from($data);
        return $dto;
    }

    /**
     * Convert the DTO to a JSON string.
     *
     * @return string The DTO represented as a JSON string.
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * Create a new instance of the DTO based on a JSON string.
     *
     * @param string $json The JSON string to populate the DTO from.
     * @return self         The new instance of the DTO.
     */
    public static function fromJson(string $json)
    {
        $data = json_decode($json, true);
        $dto = new static();
        $dto->from($data);
        return $dto;
    }

    /**
     * Retrieve an instance of DTO from the given source
     *
     * @param mixed $source
     * @return self
     */
    public static function from($source)
    {
        if ($source instanceof Enumerable) {
            $source = $source->all();
        } elseif ($source instanceof Arrayable) {
            $source = $source->toArray();
        } elseif ($source instanceof Jsonable) {
            $source = json_decode($source->toJson(), true);
        } elseif ($source instanceof JsonSerializable) {
            $source = $source->jsonSerialize();
        } elseif ($source instanceof Traversable) {
            $source = iterator_to_array($source);
        }

        return static::make((array)$source);
    }

    /**
     * Get a string representation of the DTO object.
     *
     * @return string The string representation of the DTO object.
     */
    public function __toString()
    {
        return static::class;
    }

    /**
     * Retrieve a clone of the DTO
     *
     * @return
     */
    public function clone()
    {
        return clone $this;
    }

    /**
     * Retrieve the serialized DTO
     *
     * @return string
     */
    public function serialize()
    {
        return serialize([
            $this->toArray()
        ]);
    }

    /**
     * Convert the DTO to an associative array.
     *
     * @return array The DTO represented as an associative array.
     */
    public function toArray()
    {
        // $properties = get_class_vars(get_class($this));
        $properties = $this->getProperties();

        $data = [];
        foreach ($properties as $key => $value) {

            $propertyValue = $this->{$key} ?? $value;

            if ($propertyValue instanceof Arrayable) {
                $propertyValue = $propertyValue->toArray();
            }

            $data[Str::snake($key)] = $propertyValue;
        }

        return $data;
    }

    /**
     * Retrieve the unserialized DTO
     *
     * @param mixed $serialized
     * @return void
     */
    public function unserialize($serialized)
    {
        [$data] = unserialize($serialized);

        $this->__construct($data);
    }

    /**
     * Determine how to clone the DTO
     *
     * @return void
     */
    public function __clone()
    {
        foreach ($this->properties as &$property) {
            $property = clone $property;
        }
    }
}
