<?php

include_once 'crawler_taipei.php';

$crawlers = array();

/**
 *  Add crawler to the array and it will be run in sequence
 */
$crawlers[] = new crawlerTaipei;

foreach ($crawlers as $crawler)
{
    $crawler->run();
}

?>