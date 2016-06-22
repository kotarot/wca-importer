<?php

define('WCA_EXPORT_DIR',  'https://www.worldcubeassociation.org/results/misc/');
define('WCA_EXPORT_HTML', 'export.html');

define('DOWNLOADS_DIR', dirname(__FILE__) . '/downloads');
define('LAST_UPDATED', dirname(__FILE__) . '/last');
define('CONFIG_FILE', dirname(__FILE__) . '/config.json');


// Fetch the lates SQL filename from WCA results export.
function find_latest_export() {
    $html = file_get_contents(WCA_EXPORT_DIR . WCA_EXPORT_HTML)
        or exit('Failed to access ' . WCA_EXPORT_DIR . WCA_EXPORT_HTML);
    preg_match('/(\'|\").*\.sql\.zip(\'|\")/', $html, $matches);
    return preg_replace('/(\'|\")/', '', $matches[0]);
}

// Returns the lastly imported SQL filename.
function get_last_imported() {
    if (file_exists(LAST_UPDATED) && $last = file(LAST_UPDATED))
        return rtrim($last[0]);
    else
        return '';
}

// Downloads SQL file, and saves it.
function download_sql($latest_sql) {
    echo 'Downloading ' . WCA_EXPORT_DIR . $latest_sql . " ...\n";
    $zipped = file_get_contents(WCA_EXPORT_DIR . $latest_sql)
        or exit('Failed to access ' . WCA_EXPORT_DIR . $latest_sql);
    file_put_contents(DOWNLOADS_DIR . '/' . $latest_sql, $zipped)
        or exit('Failed to write data to ' . DOWNLOADS_DIR . '/' . $latest_sql);
    echo "Successfully downloaded.\n";
}

// Extracts the imported file, and imports it.
function import_sql($latest_sql) {
    $conf = file_get_contents(CONFIG_FILE);
    $confobj = json_decode($conf);

    echo "Extracting to " . DOWNLOADS_DIR . "/ ...\n";
    $zip = new ZipArchive();
    $res = $zip->open(DOWNLOADS_DIR . '/' . $latest_sql)
        or exit('Failed to open ' . DOWNLOADS_DIR . '/' . $latest_sql);
    $zip->extractTo(DOWNLOADS_DIR . '/');
    $zip->close();
    echo "Successfully extracted\n";

    echo "Importing into DB ...\n";

    // Append 'SET NAMES utf8;'
    $command = 'cat ' . dirname(__FILE__) . '/set_names_utf8.sql '
             . DOWNLOADS_DIR . '/WCA_export.sql '
             . '>' . DOWNLOADS_DIR . '/WCA_export.set_names_utf8.sql';
    $ret = system($command, $retval);
    if ($ret === false || $retval !== 0)
        exit('Failed: cat.');

    // Import
    $command = 'mysql -h ' . $confobj->MYSQL_HOST . ' -u ' . $confobj->MYSQL_USER . ' -p' . $confobj->MYSQL_PASS
             . ' --default-character-set=utf8 ' . $confobj->MYSQL_DB
             . ' < ' . DOWNLOADS_DIR . '/WCA_export.set_names_utf8.sql';
    $ret = system($command, $retval);
    if ($ret === false || $retval !== 0)
        exit('Failed to import to MySQL.');

    // Index
    $command = 'mysql -h ' . $confobj->MYSQL_HOST . ' -u ' . $confobj->MYSQL_USER . ' -p' . $confobj->MYSQL_PASS
             . ' --default-character-set=utf8 ' . $confobj->MYSQL_DB
             . ' < ' . dirname(__FILE__) . '/create-indexes.sql';
    $ret = system($command, $retval);
    if ($ret === false || $retval !== 0)
        exit('Failed to create indexes.');

    // Stores last imported filename into file,
    // so that we can check it at the next time.
    file_put_contents(LAST_UPDATED, $latest_sql)
        or exit('Failed to write data to ' . LAST_UPDATED);
    unlink(DOWNLOADS_DIR . '/README.txt');
    unlink(DOWNLOADS_DIR . '/WCA_export.sql');
    unlink(DOWNLOADS_DIR . '/WCA_export.set_names_utf8.sql');
    unlink(DOWNLOADS_DIR . '/' . $latest_sql);

    echo "Successfully imported.\n";
}


function main() {
    $latest_export = find_latest_export();
    $last_imported = get_last_imported();
    if ($latest_export !== $last_imported) {
        download_sql($latest_export);
        import_sql($latest_export);
    } else {
        echo "You do not need to update.\n";
    }
}
main();
