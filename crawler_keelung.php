<?php

include_once 'crawlerBase.php';

class crawlerKeelung extends crawlerBase
{
    public $targetUrl = "http://www.klcg.gov.tw/social/home.jsp?contlink=ap/news_view.jsp&dataserno=201111220003";
    public $refererUrl = "http://www.klcg.gov.tw/social/home.jsp?contlink=ap/news_view.jsp&dataserno=201111220003";
    public $targetId = "00";
    public $targetName = "基隆市";
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
        $violationTable = $domTables->item(16);

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
        // Keelung offers violation report as image in pdf, we can't handle that
        // now.
        // Just download the file in link.
        $links = $violationTable->getElementsByTagName('a');
        $docLink = $links->item(1);

        $attachmentUrl = $this->getUrlPath($this->targetUrl) . $docLink->getAttribute('href');
        $this->downloadUrl($attachmentUrl, "基隆市違反勞動基準法事業單位事業主公布_", ".pdf");

        return true;
    }
}

?>
