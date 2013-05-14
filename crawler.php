<?php

include_once 'crawler_taipei.php';

$crawlers = array();
$runDate = date("Y_m_d_H_i_s");

/**
 *  Add crawler to the array and it will be run in sequence
 */
$crawlers[] = new crawlerTaipei;

foreach ($crawlers as $crawler)
{
    if ($crawler->run($runDate))
    {
        echo "******* " . $crawler->targetName . " Updated.\n";
    }
}

?>