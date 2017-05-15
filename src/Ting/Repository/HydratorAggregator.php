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

namespace CCMBenchmark\Ting\Repository;

use CCMBenchmark\Ting\Serializer\RuntimeException;

class HydratorAggregator extends Hydrator
{
    /**
     * @var callable
     */
    protected $callableForId;

    /**
     * @var callable
     */
    protected $callableForData;

    /**
     * @var callable
     */
    protected $callableFinalizeAggregate;

    /**
     * @var bool
     */
    protected $BCBreak = false;

    /**
     * Enable BC Break will throw a RuntimeException when your collection is not sorted
     * by the identifier you want to aggregate.
     */
    public function enableBCBreak()
    {
        $this->BCBreak = true;
    }

    /**
     * @param callable $callableForId
     * @return $this
     */
    public function callableIdIs(callable $callableForId)
    {
        $this->callableForId = $callableForId;
        return $this;
    }

    /**
     * @param callable $callableForData
     * @return $this
     */
    public function callableDataIs(callable $callableForData)
    {
        $this->callableForData = $callableForData;
        return $this;
    }

    /**
     * @param callable $callableFinalizeAggregate
     * @return $this
     */
    public function callableFinalizeAggregate(callable $callableFinalizeAggregate)
    {
        $this->callableFinalizeAggregate = $callableFinalizeAggregate;
        return $this;
    }

    /**
     * @throws RuntimeException This exception is only throwed if you enableBCBreak
     *
     * @return \Generator
     */
    public function getIterator()
    {
        $knownIdentifiers = [];
        $callableForId = $this->callableForId;
        $callableForData = $this->callableForData;
        $previousId = null;
        $previousResult = null;
        $previousKey = null;
        $currentId = null;
        $aggregate = [];
        $key = null;
        $result = null;

        foreach ($this->result as $key => $columns) {
            $result = $this->hydrateColumns(
                $this->result->getConnectionName(),
                $this->result->getDatabase(),
                $columns
            );

            $currentId = $callableForId($result);

            if (in_array($currentId, $knownIdentifiers, true) === true) {
                if ($this->BCBreak === true) {
                    throw new RuntimeException("Identifier $currentId already generated, please sort your result by identifier");
                }

                continue;
            }

            if ($previousId === null) {
                $previousId = $currentId;
                $previousResult = $result;
                $previousKey = $key;
            }

            if ($previousId === $currentId) {
                $aggregate[] = $callableForData($result);
            } else {
                $previousResult = $this->finalizeAggregate($previousResult, $aggregate);

                $knownIdentifiers[] = $previousId;

                yield $previousKey => $previousResult;

                $aggregate = [$callableForData($result)];
                $previousId = $currentId;
                $previousResult = $result;
                $previousKey = $key;
            }
        }

        if ($previousId === $currentId) {
            yield $key => $this->finalizeAggregate($result, $aggregate);
        }
    }

    /**
     * @param mixed $result
     * @param mixed $aggregate
     *
     * @return mixed
     */
    private function finalizeAggregate($result, $aggregate)
    {
        if ($this->callableFinalizeAggregate === null) {
            $result['aggregate'] = $aggregate;
            return $result;
        }

        $callableFinalizeAggregate = $this->callableFinalizeAggregate;
        return $callableFinalizeAggregate($result, $aggregate);
    }
}
