<?php

namespace Kzu\Database;

use Kzu\Normalizer\Table;
use Kzu\Database\Database;

Trait DatabaseQuery {
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

    static public function update(string $db_name, ?array $where = [], ?array $parameters = []): bool {
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
                if ($remove === false):
                    $rows[] = $row;
                endif;
            endif;
        endforeach;

        $database['config'] = Database::config($db_name);
        $database['rows'] = $rows ?? [];

        return Database::persist($db_name, $database) ?? false;
    }

    static public function findOneBy(string $db_name, ?array $parameters = [], ?bool $parsed = false): ?array {
        foreach (Database::rows($db_name) ?? [] as $row):
            $excluded = false;
            if (!empty($parameters)):
                $excluded = true;
                foreach ($parameters ?? [] as $key => $value):
                    if (!empty($row[$key]) && $row[$key] == $value):
                        $excluded = false;
                    else: $excluded = true; endif;
                endforeach;
                if ($excluded === false):
                    if ($parsed): return Table::arrayOneLine($row);
                    else: return $row; endif;
                endif;
            endif;
        endforeach;

        return null;
    }

    static public function findBy(string $db_name, ?array $parameters = [], ?bool $parsed = false): ?array {
        foreach (Database::rows($db_name) ?? [] as $row):
            $excluded = false;
            if (!empty($parameters)):
                $excluded = true;
                foreach ($parameters ?? [] as $key => $value):
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