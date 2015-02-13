<?php

// =============================================================================
// == Application constants ====================================================
// =============================================================================

// Directories
define('DIR_ROOT', '/mnt/data/ginkgo');
define('DIR_UPLOADS', DIR_ROOT . '/uploads');

// URLs
define('URL_ROOT', 'http://qb.cshl.edu/ginkgo');
define('URL_UPLOADS', URL_ROOT . '/uploads');

// Minimum number of cells to perform an analysis
$GINKGO_MIN_NB_CELLS = 3;


// =============================================================================
// == Misc. configuration ======================================================
// =============================================================================
set_time_limit(0);
error_reporting(E_ALL);
session_start();


// =============================================================================
// == Helper functions =========================================================
// =============================================================================

// Generate random ID of arbitrary length
function generateID($length = 20)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Return list of files uploaded by user (excluding . and ..)
function getMyFiles($userID, $ext = 'bed')
{
	$directory = DIR_UPLOADS . '/' . $userID . '/';
	//$files = array_diff(scandir($directory), array('..', '.'));
	$files = array_diff(glob($directory . '/*.' . $ext), array('..', '.'));
	$files2= array_diff(glob($directory . '/*.' . $ext . '.gz'), array('..', '.'));
	$files = array_merge($files, $files2);

	// file_put_contents('wtf', print_r($files, true));

	$result = array();
	foreach($files as $file)
		if(pathinfo($filename, PATHINFO_EXTENSION) != $ext)
			$result[] = basename($file);
	return $result;
}

//
function sanitize(&$item, $key)
{
	$item = escapeshellarg($item);
}

// Modified from http://stackoverflow.com/questions/15188033/human-readable-file-size
function humanFileSize($fileDir, $unit = "")
{
	$size = filesize($fileDir);

	if( (!$unit && $size >= 1<<30) || $unit == "GB")
		return number_format($size/(1<<30), 2) . " GB";
	if( (!$unit && $size >= 1<<20) || $unit == "MB")
		return number_format($size/(1<<20), 2) . " MB";
	if( (!$unit && $size >= 1<<10) || $unit == "KB")
		return number_format($size/(1<<10), 0) . " KB";

	return number_format($size)." bytes";
}
