<?php
declare(strict_types=1);

namespace WoohooLabs\Yang\JsonApi\Schema;

final class JsonApi
{
    /**
     * @var string
     */
    private $version;

    /**
     * @var array
     */
    private $meta;

    public function __construct(string $version, array $meta)
    {
        $this->version = $version;
        $this->meta = $meta;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function hasMeta(): bool
    {
        return empty($this->meta) === false;
    }

    public function meta(): array
    {
        return $this->meta;
    }

    public static function fromArray(array $array): JsonApi
    {
        $version = isset($array["version"]) && is_string("version") ? $array["version"] : "1.0";
        $meta = isset($array["meta"]) && is_array($array["meta"]) ? $array["meta"] : [];

        return new self($version, $meta);
    }

    /**
     * @internal
     */
    public function toArray(): array
    {
        $result = [];

        if ($this->version) {
            $result["version"] = $this->version;
        }

        if (empty($this->meta) === false) {
            $result["meta"] = $this->meta;
        }

        return $result;
    }
}
