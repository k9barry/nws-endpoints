<?php

/**
 * fcn_csvToSqlite
 *
 * @param  mixed $db_conn
 * @param  mixed $csvFilePath
 * @param  mixed $options
 * @param  mixed $logger
 * @return void
 */
function fcn_csvToSqlite($db_conn, $csvFilePath, $options = array(), $logger)
{
    global $CfsTableName;
    extract($options);
    if (($csv_handle = fopen($csvFilePath, "r")) === false) {
        throw new Exception('Cannot open CSV file');
    }
    $delimiter = ',';
    $table = $CfsTableName;
    $fields = array_map(function ($field) {
        return strtolower(preg_replace("/[^A-Z0-9]/i", '', $field));
    }, fgetcsv($csv_handle, 0, $delimiter));
    $create_fields_str = join(', ', array_map(function ($field) {
        return "$field TEXT NULL";
    }, $fields));
    $db_conn->beginTransaction();
    $create_table_sql = "CREATE TABLE IF NOT EXISTS $table ($create_fields_str)";
    $db_conn->exec($create_table_sql);
    $insert_fields_str = join(', ', $fields);
    $insert_values_str = join(', ', array_fill(0, count($fields), '?'));
    $insert_sql = "INSERT INTO $table ($insert_fields_str) VALUES ($insert_values_str)";
    $insert_sth = $db_conn->prepare($insert_sql);
    $inserted_rows = 0;
    while (($data = fgetcsv($csv_handle, 0, $delimiter)) !== false) {
        $insert_sth->execute($data);
        $inserted_rows++;
    }
    $db_conn->commit();
    fclose($csv_handle);
    $logger->info("Table " . $table . " created");
    return array(
        'table' => $table,
        'fields' => $fields,
        'insert' => $insert_sth,
        'inserted_rows' => $inserted_rows,
    );
}
