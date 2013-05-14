<?php

include_once 'crawler_keelung.php';
include_once 'crawler_taipei.php';

$crawlers = array();
$updateIndexList = array();
$runDate = date("Y_m_d_H_i_s");

/**
 *  Add crawler to the array and it will be run in sequence
 */
$crawlers[] = new crawlerKeelung;
$crawlers[] = new crawlerTaipei;

// Run scarwlers
echo "************************\n";
echo "*    Crawlers Start    *\n";
echo "************************\n";
foreach ($crawlers as $i => $crawler) {
    if ($crawler->run($runDate)) {
        $updateIndexList[] = $i;
    }
}

// Update report
echo "*********************\n";
echo "*    Update List    *\n";
echo "*********************\n";
foreach ($updateIndexList as $i) {
    echo $crawlers[$i]->targetId . " (" . $crawlers[$i]->targetName . ")\n";
}

?>