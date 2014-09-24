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

namespace tests\units\CCMBenchmark\Ting\Repository;

use CCMBenchmark\Ting\ConnectionPoolInterface;
use CCMBenchmark\Ting\Query\PreparedQuery;
use CCMBenchmark\Ting\Query\Query;
use mageekguy\atoum;

class Repository extends atoum
{
    public function testExecuteShouldExecuteQuery()
    {
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->connect =
            function ($connectionConfig, $database, $connectionType, \Closure $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $services  = new \CCMBenchmark\Ting\Services();
        $mockQuery = new \mock\CCMBenchmark\Ting\Query\Query('SELECT * FROM bouh');
        $this->calling($mockQuery)->execute =
            function ($collection) use (&$outerCollection) {
                $outerCollection = $collection;
            };

        $collection = new \CCMBenchmark\Ting\Repository\Collection();

        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator'),
                $services->get('UnitOfWork')
            ))
            ->then($repository->execute($mockQuery, $collection))
            ->object($outerCollection)
                ->isIdenticalTo($collection);
    }

    public function testExecuteShouldReturnACollectionIfNoParam()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionConfig, $database, $connectionType, \Closure $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $mockQuery = new \mock\CCMBenchmark\Ting\Query\Query('SELECT * FROM bouh');
        $this->calling($mockQuery)->execute =
            function ($collection) use (&$outerCollection) {
                $outerCollection = $collection;
            };
        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator'),
                $services->get('UnitOfWork')
            ))
            ->then($repository->execute($mockQuery))
            ->object($outerCollection)
                ->isInstanceOf('\CCMBenchmark\Ting\Repository\Collection');
    }

    public function testExecutePreparedShouldPrepareAndExecuteQuery()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionConfig, $database, $connectionType, \Closure $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $mockQuery = new \mock\CCMBenchmark\Ting\Query\PreparedQuery(
            'SELECT * FROM bouh WHERE truc = :bidule'
        );
        $this->calling($mockQuery)->prepare =
            function () use ($mockQuery) {
                return $mockQuery;
            }
        ;
        $this->calling($mockQuery)->execute =
            function ($collection) use (&$outerCollection) {
                $outerCollection = $collection;
            };

        $collection = new \CCMBenchmark\Ting\Repository\Collection();

        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator'),
                $services->get('UnitOfWork')
            ))
            ->then($repository->executePrepared($mockQuery, $collection))
            ->object($outerCollection)
                ->isIdenticalTo($collection);
    }

    public function testExecutePreparedShouldReturnACollectionIfNoParam()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionConfig, $database, $connectionType, \Closure $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $mockQuery = new \mock\CCMBenchmark\Ting\Query\PreparedQuery(
            'SELECT * FROM bouh WHERE truc = :bidule'
        );
        $this->calling($mockQuery)->prepare =
            function () use ($mockQuery) {
                return $mockQuery;
            }
        ;
        $this->calling($mockQuery)->execute =
            function ($collection) use (&$outerCollection) {
                $outerCollection = $collection;
            };
        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator'),
                $services->get('UnitOfWork')
            ))
            ->then($repository->executePrepared($mockQuery))
            ->object($outerCollection)
                ->isInstanceOf('\CCMBenchmark\Ting\Repository\Collection');
    }

    public function testGet()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $driverFake         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($driverFake);
        $mockMysqliResult   = new \mock\tests\fixtures\FakeDriver\MysqliResult(array());

        $this->calling($driverFake)->query = $mockMysqliResult;

        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = array();
            $stdClass = new \stdClass();
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'boo_id';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'prenom';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            return $fields;
        };

        $this->calling($mockMysqliResult)->fetch_array = function ($type) {
            return array(3, 'Sylvain');
        };

        $this->calling($mockConnectionPool)->connect =
            function ($connectionConfig, $database, $connectionType, \Closure $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $bouh = new \tests\fixtures\model\Bouh();
        $bouh->setId(3);
        $bouh->setfirstname('Sylvain');

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator'),
                $services->get('UnitOfWork')
            ))
            ->and($testBouh = $bouhRepository->get(3))
            ->integer($testBouh->getId())
                ->isIdenticalTo($bouh->getId())
            ->string($testBouh->getFirstname())
                ->isIdenticalTo($bouh->getFirstname());
    }

    public function testGetOnMaster()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockMetadataFactory= new \mock\CCMBenchmark\Ting\Repository\MetadataFactory($services->get('QueryFactory'));
        $mockMetadata       = new \mock\CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory'));
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);
        $mockMysqliResult   = new \mock\tests\fixtures\FakeDriver\MysqliResult(array());
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $this->calling($mockMetadataFactory)->get = function () use ($mockMetadata) {
            return $mockMetadata;
        };

        $this->calling($mockConnectionPool)->connect =
            function ($connectionConfig, $database, $connectionType, \Closure $callback) use ($mockDriver) {
                $callback($mockDriver);
            };



        $this->calling($fakeDriver)->query = $mockMysqliResult;

        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = array();
            $stdClass = new \stdClass();
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'boo_id';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'prenom';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            return $fields;
        };

        $this->calling($mockMysqliResult)->fetch_array = function ($type) {
            return array(3, 'Sylvain');
        };

        $this
            ->if(
                $bouhRepository = new \tests\fixtures\model\BouhRepository(
                    $mockConnectionPool,
                    $services->get('MetadataRepository'),
                    $mockMetadataFactory,
                    $services->get('Collection'),
                    $services->get('Hydrator'),
                    $services->get('UnitOfWork')
                )
            )
            ->and($bouhRepository->get(3, null, null, ConnectionPoolInterface::CONNECTION_MASTER))
                ->mock($mockMetadata)
                    ->call('connectMaster')
                    ->once();
        ;
    }

    public function testExecuteWithConnectionTypeShouldCallMetadataConnectWithConnectionType()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockMetadataFactory= new \mock\CCMBenchmark\Ting\Repository\MetadataFactory($services->get('QueryFactory'));
        $mockMetadata       = new \mock\CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory'));

        $this->calling($mockMetadataFactory)->get = function () use ($mockMetadata) {
            return $mockMetadata;
        };

        $this->calling($mockMetadata)->connect = function (
            ConnectionPoolInterface $connectionPool,
            $connectionType,
            \Closure $callback
        ) use (
            &$outerConnectionType
        ) {
            $outerConnectionType = $connectionType;
        };

        $this
            ->if(
                $bouhRepository = new \tests\fixtures\model\BouhRepository(
                    $services->get('ConnectionPool'),
                    $services->get('MetadataRepository'),
                    $mockMetadataFactory,
                    $services->get('Collection'),
                    $services->get('Hydrator'),
                    $services->get('UnitOfWork')
                )
            )->and($query = new Query(''))
            ->and(
                $bouhRepository->execute(
                    $query,
                    $services->get('Collection'),
                    ConnectionPoolInterface::CONNECTION_MASTER
                )
            )
                ->integer($outerConnectionType)
                    ->isEqualTo(ConnectionPoolInterface::CONNECTION_MASTER)
        ;
    }

    public function testExecutePreparedWithConnectionTypeShouldCallMetadataConnectWithConnectionType()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockMetadataFactory= new \mock\CCMBenchmark\Ting\Repository\MetadataFactory($services->get('QueryFactory'));
        $mockMetadata       = new \mock\CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory'));

        $this->calling($mockMetadataFactory)->get = function () use ($mockMetadata) {
            return $mockMetadata;
        };

        $this->calling($mockMetadata)->connect = function (
            ConnectionPoolInterface $connectionPool,
            $connectionType,
            \Closure $callback
        ) use (
            &$outerConnectionType
        ) {
            $outerConnectionType = $connectionType;
        };

        $this
            ->if(
                $bouhRepository = new \tests\fixtures\model\BouhRepository(
                    $services->get('ConnectionPool'),
                    $services->get('MetadataRepository'),
                    $mockMetadataFactory,
                    $services->get('Collection'),
                    $services->get('Hydrator'),
                    $services->get('UnitOfWork')
                )
            )->and($query = new PreparedQuery(''))
            ->and(
                $bouhRepository->executePrepared(
                    $query,
                    $services->get('Collection'),
                    ConnectionPoolInterface::CONNECTION_MASTER
                )
            )
                ->integer($outerConnectionType)
                    ->isEqualTo(ConnectionPoolInterface::CONNECTION_MASTER)
        ;
    }

    public function testStartTransactionShouldOpenTransaction()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $services->set('ConnectionPool', function ($container) use ($mockConnectionPool) {
            return $mockConnectionPool;
        });

        $this->calling($mockConnectionPool)->connect =
            function ($connectionConfig, $database, $connectionType, \Closure $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator'),
                $services->get('UnitOfWork')
            ))
            ->then($bouhRepository->startTransaction())
            ->boolean($mockDriver->isTransactionOpened())
                ->isTrue();
    }

    public function testCommitShouldCloseTransaction()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionConfig, $database, $connectionType, \Closure $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator'),
                $services->get('UnitOfWork')
            ))
            ->then($bouhRepository->startTransaction())
            ->then($bouhRepository->commit())
            ->boolean($mockDriver->isTransactionOpened())
                ->isFalse()
        ;
    }

    public function testRollbackShouldCloseTransaction()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionConfig, $database, $connectionType, \Closure $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator'),
                $services->get('UnitOfWork')
            ))
            ->then($bouhRepository->startTransaction())
            ->then($bouhRepository->rollback())
            ->boolean($mockDriver->isTransactionOpened())
                ->isFalse()
        ;
    }
}
