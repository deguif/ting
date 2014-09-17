<?php

namespace fastorm\Entity;

use fastorm\Query\QueryFactoryInterface;

class MetadataFactory implements MetadataFactoryInterface
{

    protected $queryFactory = null;

    public function __construct(QueryFactoryInterface $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function get()
    {
        return new Metadata($this->queryFactory);
    }
}
