<?php

namespace Walletable\Wallet\Details;

use Exception;

class Info
{
    /**
     * Value casts
     *
     * @var array
     */
    protected static $casts = [];

    /**
     * Value key
     *
     * @var string
     */
    protected $key;

    /**
     * Value type
     *
     * @var string
     */
    protected $type;

    /**
     * Value name
     *
     * @var string
     */
    protected $name;

    /**
     * Value
     *
     * @var string
     */
    protected $value;

    /**
     * Extra values
     *
     * @var string
     */
    protected $extras = [];

    /**
     * String value of the info
     *
     * @var string
     */
    protected $string;

    /**
     * Json value of the info
     *
     * @var string
     */
    protected $json;

    /**
     * Create new Info
     *
     * @param string $key
     * @param string $name
     * @param string $value
     * @param string $type
     * @param self
     */
    public function __construct(string $key, string $name, string $value, string $type = 'text')
    {
        $this->key = $key;
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
    }

    /**
     * Add extra values that can be useful for the caster
     *
     * @param string $name
     * @param mixed|null $value
     * @return mixed|self
     */
    public function extra(string $name, $value = null)
    {
        $result = null;

        if ($value) {
            $this->extras[$name] = $value;
            return $this;
        } else {
            $result = $this->extras[$name];
        }

        return $result;
    }

    /**
     * Create new Info
     * @static
     *
     * @param string $key
     * @param string $name
     * @param string $value
     * @param string $type
     * @param self
     */
    public static function new(string $key, string $name, string $value, string $type = 'text')
    {
        return new static($key, $name, $value, $type);
    }

    /**
     * Add a new cast
     *
     * @param string $name
     * @param string $cast
     *
     * @return void
     */
    public static function cast(string $name, string $cast)
    {
        if (
            !class_exists($cast) ||
            !is_subclass_of($cast, CastInterface::class)
        ) {
            throw new Exception(sprintf(
                'Cast class must implement %s interface',
                CastInterface::class
            ));
        }

        static::$casts[$name] = new $cast();
    }

    /**
     * Get the currenct cast
     */
    public function getCast(): CastInterface
    {
        if (!isset(static::$casts[$this->type])) {
            throw new Exception(sprintf(
                '"%s" is not a valid cast for an instance of %s',
                $this->type,
                self::class
            ));
        }

        return static::$casts[$this->type];
    }

    /**
     * Cast the Info to string
     */
    public function string(): string
    {
        if (isset($this->string)) {
            return $this->string;
        }

        return $this->string = $this->getCast()->string($this, $this->value);
    }

    /**
     * Returns the name
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * Cast the Info to string
     */
    public function __toString()
    {
        return $this->string();
    }

    /**
     * Cast the Info to string
     */
    public function json()
    {
        if (isset($this->json)) {
            return $this->json;
        }

        return $this->json = $this->getCast()->json($this, $this->value);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            $this->key => [
                'name' => $this->name,
                'type' => $this->type,
                'value' => $this->json(),
            ]
        ];
    }
}
