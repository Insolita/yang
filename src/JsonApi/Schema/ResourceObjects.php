<?php
declare(strict_types=1);

namespace WoohooLabs\Yang\JsonApi\Schema;

use WoohooLabs\Yang\JsonApi\Exception\DocumentException;

final class ResourceObjects
{
    /*
     * @var bool
     */
    private $isSinglePrimaryResource;

    /**
     * @var ResourceObject[]
     */
    private $resources = [];

    /**
     * @var ResourceObject[]
     */
    private $primaryKeys = [];

    /**
     * @var ResourceObject[]
     */
    private $includedKeys = [];

    public function __construct(array $data, array $included, bool $isSinglePrimaryResource)
    {
        $this->isSinglePrimaryResource = $isSinglePrimaryResource;

        if ($this->isSinglePrimaryResource === true) {
            if (empty($data) === false) {
                $this->addPrimaryResource(ResourceObject::fromArray($data, $this));
            }
        } else {
            foreach ($data as $resource) {
                $this->addPrimaryResource(ResourceObject::fromArray($resource, $this));
            }
        }

        foreach ($included as $resource) {
            $this->addIncludedResource(ResourceObject::fromArray($resource, $this));
        }
    }

    public function isSinglePrimaryResource(): bool
    {
        return $this->isSinglePrimaryResource === true;
    }

    public function isPrimaryResourceCollection(): bool
    {
        return $this->isSinglePrimaryResource === false;
    }

    public function hasResource(string $type, string $id): bool
    {
        return isset($this->resources["$type.$id"]);
    }

    public function resource(string $type, string $id): ResourceObject
    {
        if (isset($this->resources["$type.$id"]) === false) {
            throw new DocumentException("Document doesn't contain any resource with the '$type' type and '$id' ID!");
        }

        return $this->resources["$type.$id"];
    }

    public function hasAnyPrimaryResources(): bool
    {
        return empty($this->primaryKeys) === false;
    }

    /**
     * @return ResourceObject[]
     */
    public function primaryResources(): array
    {
        return array_values($this->primaryKeys);
    }

    /**
     * @throws DocumentException
     */
    public function primaryResource(): ResourceObject
    {
        if ($this->hasAnyPrimaryResources() === false) {
            throw new DocumentException("The document doesn't have a primary resource!");
        }

        reset($this->primaryKeys);
        $key = key($this->primaryKeys);

        return $this->resources[$key];
    }

    public function hasPrimaryResource(string $type, string $id): bool
    {
        return isset($this->primaryKeys["$type.$id"]);
    }

    public function hasAnyIncludedResources(): bool
    {
        return empty($this->includedKeys) === false;
    }

    public function hasIncludedResource(string $type, string $id): bool
    {
        return isset($this->includedKeys["$type.$id"]);
    }

    /**
     * @return ResourceObject[]
     */
    public function includedResources(): array
    {
        return array_values($this->includedKeys);
    }

    /**
     * @internal
     */
    public static function fromSinglePrimaryData(array $data, array $included): ResourceObjects
    {
        return new self($data, $included, true);
    }

    /**
     * @internal
     */
    public static function fromCollectionPrimaryData(array $data, array $included): ResourceObjects
    {
        return new self($data, $included, false);
    }

    function primaryDataToArray(): ?array
    {
        return $this->isSinglePrimaryResource ? $this->primaryResourceToArray() : $this->primaryCollectionToArray();
    }

    public function includedToArray(): array
    {
        $result = [];
        foreach ($this->includedKeys as $resource) {
            $result[] = $resource->toArray();
        }

        return $result;
    }

    private function primaryResourceToArray(): ?array
    {
        if ($this->hasAnyPrimaryResources() === false) {
            return null;
        }

        reset($this->primaryKeys);
        $key = key($this->primaryKeys);

        return $this->resources[$key]->toArray();
    }

    private function primaryCollectionToArray(): array
    {
        $result = [];
        foreach ($this->primaryKeys as $resource) {
            $result[] = $resource->toArray();
        }

        return $result;
    }

    private function addPrimaryResource(ResourceObject $resource): ResourceObjects
    {
        $type = $resource->type();
        $id = $resource->id();
        if ($this->hasIncludedResource($type, $id) === true) {
            unset($this->includedKeys["$type.$id"]);
        }

        $this->addResource($this->primaryKeys, $resource);

        return $this;
    }

    private function addIncludedResource(ResourceObject $resource): ResourceObjects
    {
        if ($this->hasPrimaryResource($resource->type(), $resource->id()) === false) {
            $this->addResource($this->includedKeys, $resource);
        }

        return $this;
    }

    private function addResource(array &$keys, ResourceObject $resource): void
    {
        $type = $resource->type();
        $id = $resource->id();
        $index = "$type.$id";

        $this->resources[$index] = $resource;
        $keys[$index] = &$this->resources[$index];
    }
}
