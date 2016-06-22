<?php

define('WCA_EXPORT_DIR',  'https://www.worldcubeassociation.org/results/misc/');
define('WCA_EXPORT_HTML', 'export.html');

define('TMP_DIR', dirname(__FILE__) . '/tmp');
define('LAST_UPDATED', dirname(__FILE__) . '/last');

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
    if ($last = file(LAST_UPDATED))
        return rtrim($last[0]);
    else
        return '';
}

// Downloads SQL file, and saves it.
function download_sql($latest_sql) {
    echo 'Downloading ' . WCA_EXPORT_DIR . $latest_sql . " ...\n";
    $zipped = file_get_contents(WCA_EXPORT_DIR . $latest_sql)
        or exit('Failed to access ' . WCA_EXPORT_DIR . $latest_sql);
    file_put_contents(TMP_DIR . '/' . $latest_sql, $zipped)
        or exit('Failed to write data to ' . TMP_DIR . '/' . $latest_sql);
    echo "Successfully downloaded.\n";
}

// Extracts the imported file, and imports it.
function import_sql($latest_sql) {
    echo "Extracting to " . TMP_DIR . "/ ...\n";
    $zip = new ZipArchive();
    $res = $zip->open(TMP_DIR . '/' . $latest_sql)
        or exit('Failed to open ' . TMP_DIR . '/' . $latest_sql);
    $zip->extractTo(TMP_DIR . '/');
    $zip->close();
    echo "Successfully extracted\n";

    echo "Importing into DB ...\n";

    // Append 'SET NAMES utf8;'
    $command = 'cat ' . dirname(__FILE__) . '/set_names_utf8.sql '
             . TMP_DIR . '/WCA_export.sql '
             . '>' . TMP_DIR . '/WCA_export.set_names_utf8.sql';
    $ret = system($command, $retval);
    if ($ret === false || $retval !== 0)
        exit('Failed: cat.');

    // Import
    $command = 'mysql -h ' . MYSQL_HOST . ' -u ' . MYSQL_USER . ' -p' . MYSQL_PASS
             . ' --default-character-set=utf8 ' . MYSQL_DB
             . ' < ' . TMP_DIR . '/WCA_export.set_names_utf8.sql';
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
    file_put_contents(LAST_UPDATED, $latest_sql)
        or exit('Failed to write data to ' . LAST_UPDATED);
    unlink(TMP_DIR . '/README.txt');
    unlink(TMP_DIR . '/WCA_export.sql');
    unlink(TMP_DIR . '/WCA_export.set_names_utf8.sql');
    unlink(TMP_DIR . '/' . $latest_sql);

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
