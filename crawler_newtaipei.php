<?php

include_once 'crawlerBase.php';

class crawlerNewTaipei extends crawlerBase
{
    public $targetUrl = "http://www.labor.ntpc.gov.tw/_file/1075/SG/46207/D.html";
    public $refererUrl = "http://www.labor.ntpc.gov.tw/_file/1075/SG/46207/D.html";
    public $targetId = "02";
    public $targetName = "新北市";
    private $_debug = false;

    /**
     *  Helper to transform Violation rule/Descriptition and Document Number into
     *  single string.
     */
    protected function getViolationRecords()
    {
        $this->debug("getViolationRecords() Start");

        // Get web content
        $content = $this->http($this->targetUrl, $this->refererUrl, false, array());
        $htmlDoc = new DOMDocument;
        @$htmlDoc->loadHTML($content);

        $domTables = $htmlDoc->getElementsByTagName('table');
        $violationTable = $domTables->item(13);

        // Check if violation table is updated
        if (!$this->checkUpdated($violationTable->nodeValue))
        {
            return false;
        }

        $this->dump("Saving backup");
        $this->saveBackup($content, "", ".html");
        $this->dump("Getting snapshot");
        $this->getSnapShot($this->targetUrl, "");

        $this->dump("Crawl!");
        // New Taipei City offers violation report in word, we can't handle that
        // now.
        // Just download the file in link.
        $reports = $violationTable->getElementsByTagName('li');
        for ($i = 0; $i < $reports->length; $i++ ) {
          $report = $reports->item($i);
          $links = $report->getElementsByTagName('a');
          $docLink = $links->item(0);

          $attachmentUrl = $docLink->getAttribute('href');
          $attachmentName = explode(".", basename($attachmentUrl));
          $this->downloadUrl($attachmentUrl, $attachmentName[0], ".".$attachmentName[1]);
        }
        return true;
    }
}

?>
