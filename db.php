<?php

function getDb(): SQLite3 {
    $db = new SQLite3('Db3.db');
    $db->busyTimeout(5000);
    $db->exec('PRAGMA journal_mode = WAL');
    return $db;
}
