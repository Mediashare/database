<?php

namespace Kzu\Database;

use Kzu\Normalizer\Yaml;
use Kzu\Normalizer\Table;
use Kzu\Filesystem\Filesystem;

/**
 * Database
 * Filesystem management for database files.
 */
Trait Database {
    static public $databases_directory;
    static public $errors = [];

    /**
     * Get configuration from database
     * @param string $db_name Database name
     * @return array|[] $config List configuration from database
     */
    static public function config(string $db_name): ?array {
        return Database::get($db_name)['config'] ?? [];
    }

    /**
     * Get model from database config
     * @param string $db_name Database name
     * @return array|[] $model List all properties from rows database
     */
    static public function model(string $db_name): ?array {
        return Database::config($db_name)['model'] ?? [];
    }

    /**
     * Get rows from database
     * @param string $db_name Database name
     * @return array|[] $rows List all entry from database
     */
    static public function rows(string $db_name): ?array {
        return Database::get($db_name)['rows'] ?? [];
    }

    /**
     * Get database content
     * @param string $db_name Database name
     */
    static public function get(string $db_name): ?array {
        $database = Database::file($db_name);
        if (!$database): 
            Database::$errors[] = "Database not found."; 
            return null; 
        endif;
        $content = Filesystem::read($database);
        if (!$content): Database::$errors[] = 'Can not read '. $db_name .' database.';
        else: $content = Yaml::parse($content); endif;
        if ($content === ''): $content = []; endif;
        return $content ?? ['config' => ['encrypted' => false, 'model' => []], 'rows' => []];
    }

    /**
     * Create database
     * @param string $db_name Database name
     * @param array|[] $model List all properties from rows database
     * @param bool $encrypted Encrypt database content
     * @return true|false Operation success
     */
    static public function create(string $db_name, ?array $model = [], ?bool $encrypted = false): bool {
        if (Database::file($db_name)): 
            Database::$errors[] = "Can not create database."; 
            return false; 
        endif;
        $filepath = rtrim(rtrim(Database::$databases_directory, '*'), '/').'/'.$db_name.'.yaml';
        if ($encrypted): $filepath = $filepath . ".encrypted"; endif;
        $content = Yaml::dump(['config' => ['encrypted' => $encrypted, 'model' => $model], 'rows' => []]);
        $result = Filesystem::write($filepath, $content, $encrypted);
        return $result;
    }

    /**
     * Persist update database
     * @param string $db_name Database name
     * @param array $database Input full database
     * @return true|false Operation success
     */
    static public function persist(string $db_name, array $database): bool {
        foreach ($rows = $database['rows'] ?? [] as $row):
            foreach (Table::getAllKeys($row) ?? [] as $key):
                if (!in_array($key, $model ?? [])):
                    $model[] = $key;
                endif;
            endforeach;
        endforeach;
        
        $database['config']['encrypted'] = $database['config']['encrypted'] ?? false;
        $database['config']['model'] = $model ?? [];
        $database['rows'] = $rows ?? [];
        
        if (!Database::file($db_name)):
            Database::create($db_name, $model, $database['config']['encrypted'] ?? false);
        elseif ($database['config']['encrypted'] !== Database::config($db_name)['encrypted'] ?? false):
            $remove_old_file = true;
        else: $remove_old_file = false; endif;

        $file = Filesystem::write(
            $old_file = Database::file($db_name) ?? $db_name,
            Yaml::dump($database), 
            $database['config']['encrypted'] ?? false
        );

        if ($file && $remove_old_file && $file !== $old_file):
            Filesystem::delete($old_file);
        endif;

        return $file ?? false;
    }

    /**
     * Delete database
     * @param string $db_name Database name
     * @return true|false Operation success
     */
    static public function delete(string $db_name): bool {
        if ($database = Database::file($db_name)):
            return unlink($database);
        endif;
        return false;
    }

    /**
     * List all databases name
     * @return array|[] Databases name
     */
    static public function list(): ?array {
        foreach (Database::files() as $database):
            $database = pathinfo($database)['filename'];
            $database = str_replace('.yaml', '', $database);
            $database = str_replace('.yml', '', $database);
            $databases[] = $database;
        endforeach;
        return $databases ?? [];
    }

    /**
     * Get file path to databases
     * @return array|[] Databases files path
     */
    static public function files(): ?array {
        $directory = rtrim(rtrim(Database::$databases_directory, '*'), '/').'/*';        
        return Filesystem::find($directory, ['yaml', 'yml', 'encrypted']) ?? [];
    }

    /**
     * Get file path to database
     * @param string $db_name Database name
     * @return string|null Database file path
     */
    static public function file(string $db_name): ?string {
        if (!empty($databases = Database::files())):
            foreach ($databases as $database):
                if (pathinfo($database)['filename'] === $db_name 
                    || pathinfo($database)['filename'] === $db_name.".yaml"
                    || pathinfo($database)['filename'] === $db_name.".yml"):
                    return $database;
                endif;
            endforeach;
            return null;
        else: return null; endif;
    }
}
