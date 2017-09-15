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

namespace CCMBenchmark\Ting\Driver\Oracle;

use CCMBenchmark\Ting\Driver\QueryException;
use CCMBenchmark\Ting\Driver\ResultInterface;

class Result implements ResultInterface
{
    /**
     * @var string|null
     */
    private $connectionName;

    /**
     * @var array|null
     */
    private $result;

    /**
     * @var string|null
     */
    private $database;

    /**
     * @var array|null
     */
    private $iteratorCurrent;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @param string $connectionName
     * @return $this
     */
    public function setConnectionName($connectionName)
    {
        $this->connectionName = (string) $connectionName;
        return $this;
    }

    /**
     * @param string $database
     * @return $this
     */
    public function setDatabase($database)
    {
        $this->database = (string) $database;
        return $this;
    }

    /**
     * @param array $result
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * @return string|null
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return int
     */
    public function getNumRows()
    {
        if (is_array($this->result) === false) {
            return 0;
        }

        return count($this->result);
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->iteratorCurrent = next($this->result);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->iteratorCurrent !== null;
    }

    /**
     * @return array|null
     */
    public function current()
    {
        return $this->iteratorCurrent;
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->iteratorCurrent = reset($this->result);
    }

    /**
     * @return int|null|string
     */
    public function key()
    {
        return key($this->result);
    }

    /**
     * Analyze the given query
     * @param $query
     * @throws QueryException
     *
     * @internal
     */
    public function setQuery($query)
    {
        $tableToAlias = [];
        $aliasToSchema = [];
        $fields = [];

        preg_match_all(
            '/(?:join|from)\s+(?:"?(?<schema>[a-z_][a-z0-9_$]+)"?.)*?"?(?<table>[a-z_][a-z0-9_$]+)"?\s*(?:as)?\s*"?(?!\b('
            . self::SQL_TABLE_SEPARATOR . ')\b)(?<alias>[a-z_][a-z0-9_$]*)?"?(\s|$)/is',
            $query,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $match['table'] = strtolower($match['table']);
            if ($match['alias'] !== '') {
                $tableToAlias[$match['table']] = strtolower($match['alias']);
                $aliasToSchema[strtolower($match['alias'])] = strtolower($match['schema']);
            } else {
                $tableToAlias[$match['table']] = $match['table'];
                $aliasToSchema[$match['table']] = strtolower($match['schema']);
            }
        }

        $tokens = preg_split('/(\W)/', strtolower($query), -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
        $tokensWithCase = preg_split('/(\W)/', $query, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);

        $startCapture = false;
        $columnsMatches = [];
        $column = '';
        $scope  = 'column';
        $brackets = 0;
        $totalTokens = count($tokens);
        $noAlias = false;

        foreach ($tokens as $index => $token) {
            if ($token === '\'') {
                if ($scope === 'string') {
                    $scope = 'column';
                } else {
                    $scope = 'string';
                }
            } elseif ($token === 'case' && $scope === 'column') {
                $scope = 'condition';
                $noAlias = true;
            } elseif ($token === 'end' && $scope === 'condition') {
                $scope = 'column';
            }

            if ($token === '(' && $scope !== 'string') {
                $brackets++;
            }

            if ($token === ')' && $scope !== 'string') {
                $brackets--;
            }

            if ($startCapture === true) {
                if ($brackets === 0 && ($token === ',' || $token === 'from' || $index === $totalTokens-1)) {
                    $scope = 'column';

                    if ($index === $totalTokens-1 && $token !== ';') {
                        $column .= $token;
                    }

                    /**
                     * Match column format table.column (as alias)
                     */
                    preg_match(
                        self::PARSE_RAW_COLUMN,
                        $column,
                        $matches
                    );

                    if ($matches !== []) {
                        $columnComponent = [
                            'complex' => false,
                            'column' => $matches['column']
                        ];

                        if (isset($matches['table']) === true) {
                            $columnComponent['table'] = $matches['table'];
                        }

                        if (isset($matches['alias']) === true) {
                            $columnComponent['alias'] = $matches['alias'];
                        }
                    } else { // Match dynamic column, ie : max(table.column), table.column || table.id, ...
                        $column = trim($column);
                        preg_match(self::PARSE_DYNAMIC_COLUMN, $column, $matches);

                        $cut = 0;

                        if ($noAlias === false) {
                            if (isset($matches['prefix']) === true) {
                                $cut += strlen($matches['prefix']);
                            }
                            if (isset($matches['alias']) === true) {
                                $cut += strlen($matches['alias']);
                            }
                        }

                        if ($cut > 0) {
                            $matches['column'] = trim(substr($column, 0, - $cut));
                        } else {
                            $matches['column'] = $column;
                        }

                        $columnComponent = [
                            'complex' => true,
                            'table' => '',
                            'column' => $matches['column']
                        ];
                        if ($noAlias === false && isset($matches['alias']) === true) {
                            $columnComponent['alias'] = $matches['alias'];
                        }
                    }

                    $columnsMatches[] = $columnComponent;
                    $column = '';
                    if ($token === 'from') {
                        break;
                    }
                    continue;
                }

                if ($scope === 'column' || $scope === 'string' || $scope === 'condition') {
                    $column .= $tokensWithCase[$index];
                }

                if ($scope === 'column' && $token === '*') {
                    throw new QueryException('Query invalid: usage of asterisk in column definition is forbidden');
                }

                if ($scope === 'column' && $token !== 'end') {
                    $noAlias = false;
                }
            }

            if ($token === 'select') {
                $startCapture = true;
            }
        }

        if ($columnsMatches === []) {
            throw new QueryException('Query invalid: can\'t parse columns');
        }

        foreach ($columnsMatches as $match) {
            $stdClass = new \stdClass();
            $stdClass->orgname = $match['column'];

            if (isset($match['alias']) === true) {
                $stdClass->name = $match['alias'];
            } else {
                $stdClass->name = $stdClass->orgname;
            }

            if ($match['complex'] === false) {
                $stdClass->orgtable = strtolower(pg_field_table($this->result, count($fields)));
            } else {
                $stdClass->orgtable = '';
            }

            if ($match['table'] !== '') {
                $stdClass->table = strtolower($match['table']);
            } elseif ($match['complex'] === false) {
                $stdClass->table = $tableToAlias[$stdClass->orgtable];
            } else {
                $stdClass->table = $stdClass->orgtable;
            }

            if (isset($aliasToSchema[$stdClass->table]) === true) {
                $stdClass->schema = $aliasToSchema[$stdClass->table];
            } else {
                $stdClass->schema = '';
            }

            $stdClass->name     = $this->unescapeField($stdClass->name);
            $stdClass->orgname  = $this->unescapeField($stdClass->orgname);
            $stdClass->table    = $this->unescapeField($stdClass->table);
            $stdClass->orgtable = $this->unescapeField($stdClass->orgtable);
            $stdClass->schema   = $this->unescapeField($stdClass->schema);

            $fields[] = $stdClass;
        }

        $this->fields = $fields;
    }

    /**
     * Unescape the given field name according to PGSQL Standards
     * @param $field
     * @return string
     */
    protected function unescapeField($field)
    {
        return trim($field, '"');
    }
}
