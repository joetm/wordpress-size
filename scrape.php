#!/usr/bin/php -q
<?php

require "vendor/autoload.php";

error_reporting(E_ALL);
set_time_limit(0);

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;


// https://gist.github.com/eusonlito/5099936
function folderSize ($dir)
{
    $size = 0;
    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        $size += is_file($each) ? filesize($each) : folderSize($each);
    }
    return $size;
}


$files = [];


$client = new Client();

$crawler = $client->request('GET', 'https://wordpress.org/download/release-archive/');

$crawler
	->filter('table')
	->first() // only the first table on the page
	->filter('tr')
	// ->reduce(function (Crawler $node, $i) {
	// 	// skip table header
	// 	return ($i == 0 ? false : true);
	// })
	->each(function ($node) {

		global $files;

	    $tr = $node->html();
	    // print $tr . "\n";

	    //get the version
	    $match = [];
	    preg_match("~<td>(.*)</td>~iU", $tr, $match);
	    $version = false;
	    if (isset($match[1])) {
		    $version = $match[1];
		}
	    print $version . "\n";

	    //get the download link
	    $match = [];
	    preg_match('~<a\shref=\"(.*)\">zip</a>~iU', $tr, $match);
	    $link = false;
	    if (isset($match[1])) {
		    $link = $match[1];
		}
	    print $link . "\n\n";

	    //get the md5 link
		// $match = [];
		// preg_match('~<a\shref=\"(.*)\">md5</a>~iU', $tr, $match);
		// $md5 = false;
		// if (isset($match[1])) {
		//     $md5 = $match[1];
		// }
		// print $md5 . "\n\n";

	    if ($version && $link) {
			$files[] = [
				"version" => $version,
				"url" => $link,
			];
		}

	})
	;

// process the files
//var_dump($files);
for ($i = 0, $s = count($files); $i < $s; $i++) {

	$url = $files[$i]['url'];

	$filename = basename($url);

	$foldername = str_replace(".zip", "", $filename);

	$zipfile = 'downloads/'.$filename;

	// don't need to download the file again, if it's already there...
	if (!file_exists($zipfile)) {
		// download the link
		file_put_contents($zipfile, fopen($url, 'r'));
	}

	// get the size of the zip file
	$zipsize = filesize($zipfile);
	$files[$i]['zipsize'] = $zipsize;

	$folder = 'downloads/'.$foldername;

	// only extract once
	if (!is_dir($folder)) {
		// make the dir
		mkdir($folder, 0777);
		// extract the zip file
		$zip = new ZipArchive;
		$res = $zip->open($zipfile);
		if ($res === TRUE) {
			$zip->extractTo($folder);
			$zip->close();
		} else {
			// zip extraction failed
			// TODO
		}
	}

	// get the size of the folder
	$foldersize = folderSize($folder);
	$files[$i]['foldersize'] = $foldersize;

}

// var_dump($files);

// write to file
$fo = fopen('output.json', 'w');
fwrite($fo, json_encode($files));
fclose($fo);


// clean-up
// TODO

