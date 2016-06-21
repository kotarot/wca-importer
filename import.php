<?php

define('WCA_EXPORT_DIR',  'https://www.worldcubeassociation.org/results/misc/');
define('WCA_EXPORT_HTML', 'export.html');

include_once dirname(__FILE__) . '/config.php';


// Fetch the lates SQL filename from WCA results export.
function find_latest_export() {
    $html = file_get_contents(WCA_EXPORT_DIR . WCA_EXPORT_HTML)
        or exit('Failed to access ' . WCA_EXPORT_DIR . WCA_EXPORT_HTML);
    preg_match('/(\'|\").*\.sql\.zip(\'|\")/', $html, $matches);
    return preg_replace('/(\'|\")/', '', $matches[0]);
}

// Returns the lastly imported SQL filename.
function get_last_imported() {
    if ($last = file(dirname(__FILE__) . '/last'))
        return rtrim($last[0]);
    else
        return '';
}

// Downloads SQL file, and saves it.
function download_sql($latest_sql) {
    echo 'Downloading ' . WCA_EXPORT_DIR . $latest_sql . "\n";
    $zipped = file_get_contents(WCA_EXPORT_DIR . $latest_sql)
        or exit('Failed to access ' . WCA_EXPORT_DIR . $latest_sql);
    file_put_contents(dirname(__FILE__) . '/' . $latest_sql, $zipped)
        or exit('Failed to write data to ' . dirname(__FILE__) . '/' . $latest_sql);
    echo "Successfully downloaded.\n";
}

// Extracts the imported file, and imports it.
function import_sql($latest_sql) {
    $zip = new ZipArchive();
    $res = $zip->open(dirname(__FILE__) . '/' . $latest_sql)
        or exit('Failed to open ' . $latest_sql);
    $zip->extractTo(dirname(__FILE__) . '/');
    $zip->close();
    echo "Extracted to " . dirname(__FILE__) . "/\n";

    $command = 'mysql -h ' . MYSQL_HOST . ' -u ' . MYSQL_USER . ' -p' . MYSQL_PASS
             . ' --default-character-set=utf8 ' . MYSQL_DB
             . ' < ' . dirname(__FILE__) . '/WCA_export.sql';
    $ret = system($command, $retval);
    if ($ret === false || $retval !== 0)
        exit('Failed to import to MySQL.');

    // Index
    $command = 'mysql -h ' . MYSQL_HOST . ' -u ' . MYSQL_USER . ' -p' . MYSQL_PASS
             . ' --default-character-set=utf8 ' . MYSQL_DB
             . ' < ' . dirname(__FILE__) . '/create-indexes.sql';
    $ret = system($command, $retval);
    if ($ret === false || $retval !== 0)
        exit('Failed to create indexes.');

    // Stores last imported filename into file,
    // so that we can check it at the next time.
    file_put_contents(dirname(__FILE__) . '/last', $latest_sql)
        or exit('Failed to write data to ' . dirname(__FILE__) . '/last');
    unlink(dirname(__FILE__) . '/README.txt');
    unlink(dirname(__FILE__) . '/WCA_export.sql');
    unlink(dirname(__FILE__) . '/' . $latest_sql);

    echo "Successfully imported.\n";
}


// ENTRY POINT
$latest_export = find_latest_export();
$last_imported = get_last_imported();
if ($latest_export !== $last_imported) {
    download_sql($latest_export);
    import_sql($latest_export);
} else {
    echo "You do not need to update.\n";
}
