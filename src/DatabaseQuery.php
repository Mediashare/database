<?php

namespace Kzu\Database;

use Kzu\Normalizer\Table;
use Kzu\Database\Database;

Trait DatabaseQuery {
    /**
     * Insert one or more rows into a specified database.
     * @param string $db_name The name of the database to insert into.
     * @param array $rows An array of rows to insert into the database.
     * @return bool True if the insert was successful, false otherwise.
     */
    static public function insert(string $db_name, array $rows): bool {
        // Get database content
        $database = Database::get($db_name);
        if (!$database): 
            Database::create($db_name); 
            $database = Database::get($db_name);
        endif;
        // Add rows
        $database['rows'] = array_merge($database['rows'] ?? [], $rows);
        // Write update
        return Database::persist($db_name, $database) ?? false;
    }

    /**
     * Update records in a database based on specified parameters
     * @param string $db_name The name of the database to update.
     * @param array|null $where (optional) An array of parameters to specify which records to update. 
     * @param array|null $parameters An array of parameters to update the selected records with.
     * @return bool Whether the update was successful or not.
     */
    static public function update(string $db_name, ?array $where = [], array $parameters): bool {
        foreach (DatabaseQuery::findBy($db_name, $where) ?? [] as $row):
            foreach ($parameters as $key => $value):
                $row[$key] = $value;
            endforeach;
            $rows[] = $row;
        endforeach;

        $database['config'] = Database::config($db_name);
        $database['rows'] = $rows ?? [];

        return Database::persist($db_name, $database) ?? false;
    }
    
    /**
    * Remove records from a database based on specified criteria.
    * @param string $db_name The name of the database to remove records from.
    * @param array|null $where (optional) An array of criteria to match records for removal.
    * @return bool Whether the removal was successful.
    */
    static public function remove(string $db_name, ?array $where = []): bool {
        foreach (Database::rows($db_name) as $row):
            $remove = true;
            if (!empty($where)):
                $remove = false;
                foreach ($where as $key => $value):
                    if (!empty($row[$key]) && $row[$key] == $value):
                        $remove = true;
                    endif;
                endforeach;
            endif;
            if ($remove === false):
                $rows[] = $row;
            endif;
        endforeach;

        $database['config'] = Database::config($db_name);
        $database['rows'] = $rows ?? [];

        return Database::persist($db_name, $database) ?? false;
    }

    /**
     * Find one record in a database based on specified parameters.
     * @param string $db_name The name of the database to search.
     * @param array|null $where (optional) An array of parameters to filter the result.
     * @param bool|null $parsed (optional) Whether to parse the result as a single-line array.
     * @return array|null An array of elements corresponding to the result found in the database, or null if no result were found.
     */
    static public function findOneBy(string $db_name, ?array $where = [], ?bool $parsed = false): ?array {
        foreach (Database::rows($db_name) ?? [] as $row):
            $excluded = false;
            if (!empty($where)):
                $excluded = true;
                foreach ($where ?? [] as $key => $value):
                    if (!empty($row[$key]) && $row[$key] == $value):
                        $excluded = false;
                    else: $excluded = true; endif;
                endforeach;
            endif;
            if ($excluded === false):
                if ($parsed): return Table::arrayOneLine($row);
                else: return $row; endif;
            endif;
        endforeach;

        return null;
    }

    /**
     * Find records in a database based on specified parameters.
     * @param string $db_name The name of the database to search.
     * @param array|null $where (optional) An array of parameters to filter the results.
     * @param bool|null $parsed (optional) Whether to parse the results as a single-line array.
     * @return array|null An array of elements corresponding to the results found in the database, or null if no results were found.
     */
    static public function findBy(string $db_name, ?array $where = [], ?bool $parsed = false): ?array {
        foreach (Database::rows($db_name) ?? [] as $row):
            $excluded = false;
            if (!empty($where)):
                $excluded = true;
                foreach ($where ?? [] as $key => $value):
                    if (!empty($row[$key]) && $row[$key] == $value):
                        $excluded = false;
                    endif;
                endforeach;
            endif;
            if ($excluded === false):
                if ($parsed): $results[] = Table::arrayOneLine($row);
                else: $results[] = $row; endif;
            endif;
        endforeach;

        return $results ?? null;
    }
}
