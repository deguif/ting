<?php
/***********************************************************************
 *
 * Ting - PHP Datamapper
 * ==========================================
 *
 * Copyright (C) 2014 CCM Benchmark Group. (http://www.ccmbenchmark.com)
 *
 ***********************************************************************
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you
 * may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 **********************************************************************/

namespace tests\units\CCMBenchmark\Ting\Query\Cached;

use CCMBenchmark\Ting\Repository\Collection;
use mageekguy\atoum;

class Query extends atoum
{
    public function testSetTTLShouldReturnThis()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');
        $this
            ->if($cachedQuery = new \CCMBenchmark\Ting\Query\Cached\Query('', $mockConnection))
            ->object($cachedQuery->setTtl(10))
                ->isIdenticalTo($cachedQuery)
        ;
    }

    public function testSetCacheKeyShouldReturnThis()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');
        $this
            ->if($cachedQuery = new \CCMBenchmark\Ting\Query\Cached\Query('', $mockConnection))
            ->object($cachedQuery->setCacheKey('myCacheKey'))
                ->isIdenticalTo($cachedQuery)
        ;
    }

    public function testSetVersionShouldReturnThis()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');
        $this
            ->if($cachedQuery = new \CCMBenchmark\Ting\Query\Cached\Query('', $mockConnection))
            ->object($cachedQuery->setVersion(2))
                ->isIdenticalTo($cachedQuery)
        ;
    }

    public function testSetForceShouldReturnThis()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');
        $this
            ->if($cachedQuery = new \CCMBenchmark\Ting\Query\Cached\Query('', $mockConnection))
            ->object($cachedQuery->setForce(true))
                ->isIdenticalTo($cachedQuery)
        ;
    }

    public function testQueryShouldCallOnlyCacheGetIfDataInCache()
    {
        $services              = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool    = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection        = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');
        $mockCollectionFactory = new \mock\CCMBenchmark\Ting\Repository\CollectionFactory(
            $services->get('MetadataRepository'),
            $services->get('UnitOfWork'),
            $services->get('Hydrator')
        );

        $mockMemcached = new \mock\CCMBenchmark\Ting\Cache\Memcached();
        $this->calling($mockMemcached)->get = function () {
            return [
                [
                    [
                        'name'     => 'prenom',
                        'orgName'  => 'firstname',
                        'table'    => 'bouh',
                        'orgTable' => 'T_BOUH_BOO',
                        'type'     => MYSQLI_TYPE_VAR_STRING,
                        'value'    => 'Xavier',
                    ]
                ]
            ];
        };

        $collection = new Collection();

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\Cached\Query('', $mockConnection, $mockCollectionFactory))
            ->then($query->setCache($mockMemcached))
            ->then($query->setTtl(10)->setCacheKey('myCacheKey'))
            ->object($query->query($collection))
                ->isIdenticalTo($collection)
                ->mock($mockCollectionFactory)
                    ->call('get')
                        ->never()
            ->object($query->query())
                ->isInstanceOf('\CCMBenchmark\Ting\Repository\Collection')
                ->mock($mockCollectionFactory)
                    ->call('get')
                        ->once()
            ->mock($mockMemcached)
                ->call('get')
                    ->twice()
                ->call('store')
                    ->never()
        ;
    }

    public function testQueryShouldCallCacheGetThenStoreIfDataNotInCache()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $mockMemcached = new \mock\CCMBenchmark\Ting\Cache\Memcached();
        $this->calling($mockMemcached)->get    = null;
        $this->calling($mockMemcached)->store  = true;
        $this->calling($mockConnection)->slave = $mockDriver;
        $this->calling($mockDriver)->execute   = true;

        $collection = new Collection();

        $this
            ->if($query = new \CCMBenchmark\Ting\Query\Cached\Query('', $mockConnection))
            ->then($query->setCache($mockMemcached))
            ->then($query->setTtl(10)->setCacheKey('myCacheKey'))
            ->object($query->query($collection))
                ->isIdenticalTo($collection)
            ->mock($mockMemcached)
                ->call('get')
                    ->once()
                ->call('store')
                    ->once()
        ;
    }

    public function testQueryWithoutTTLShouldRaiseException()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');
        $this
            ->if($cachedQuery = new \CCMBenchmark\Ting\Query\Cached\Query('', $mockConnection))
            ->and($cachedQuery->setCacheKey('myCacheKey'))
            ->exception(function () use ($cachedQuery) {
                $cachedQuery->query();
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Query\QueryException')
                ->hasMessage('You should call setTtl to use query method')
        ;
    }

    public function testQueryWithoutCacheKeyShouldRaiseException()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection     = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'database');
        $this
            ->if($cachedQuery = new \CCMBenchmark\Ting\Query\Cached\Query('', $mockConnection))
            ->and($cachedQuery->setTtl(10))
            ->exception(function () use ($cachedQuery) {
                $cachedQuery->query(new Collection());
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Query\QueryException')
                ->hasMessage('You must call setCacheKey to use query method')
        ;
    }
}
