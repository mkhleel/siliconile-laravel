<?php

namespace Modules\Core\Helpers;

class APIResponse implements \ArrayAccess
{
    /** @var int */
    protected $status;

    /** @var bool */
    protected $success;

    /** @var array|null|string */
    protected $error;

    /** @var array|null */
    protected $data;

    /** @var array|null */
    protected $meta;

    /**
     * ApiResponse constructor.
     *
     * @param  array|null|string  $error
     * @param  array|null  $data
     * @param  array|null  $meta
     */
    public function __construct(bool $success = true, $error = null, $data = null, $meta = null, int $status = 200)
    {
        $this->status = $status;
        $this->success = $success;
        $this->error = $error;
        $this->data = $data;
        $this->meta = $meta;
    }

    /**
     * @param  array|null  $data
     * @param  array|null  $meta
     * @return APIResponse
     */
    public static function success($data = null, $meta = null, int $status = 200)
    {
        $response = new self;

        $response->status = $status;
        $response->data = $data;
        $response->meta = $meta;

        return $response;
    }

    /**
     * @param  mixed  $error
     * @return APIResponse
     */
    public static function error($error = null, int $status = 400)
    {
        $response = new self;

        $response->status = $status;
        $response->success = false;
        $response->error = $error;

        return $response;
    }

    /**
     * @return $this
     */
    public function data(?array $data = null)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return $this
     */
    public function meta(?array $meta = null)
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * @return $this
     */
    public function status(int $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function json()
    {
        return response()->json(get_object_vars($this))
            ->setStatusCode($this->status);
    }

    /**
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @param  string  $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset])
            ? $this->data[$offset] : null;
    }

    /**
     * @param  string  $offset
     * @param  mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * @param  string  $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @return $this
     */
    public function cache($lifespan, ResponseCache $cache, $forceOverwrite = false)
    {
        if (! $cache->hasData() || $forceOverwrite) {
            $cache->cacheData($this->data, $lifespan);
        }

        return $this;
    }
}
