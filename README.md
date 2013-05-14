Crawlers set for jobhelper by ronnywang
==================

Set of crawlers for jobhelper.

1. Supported pages
   - Taipei : http://www.bola.taipei.gov.tw/ct.asp?xItem=41223990&ctNode=62846&mp=116003
   - Keelung : http://www.klcg.gov.tw/social/home.jsp?contlink=ap/news_view.jsp&dataserno=201111220003

2. Required package
   - php5
   - php5_curl

3. How to run
   php crawler.php
   *works on linux only.

4. Features
   - Check if data has been updated before parsing.
   - Export crawled data in CSV form.
   - Backup crawled pages.
   - Take snapshot of web page via a handy site I found, http://www.hiqpdf.com/demo/ConvertHtmlToImage.aspx

5. TODO list
   - Crawler for violation records in html form.
   - Crawler for violation records in pdf form.
     *Try use tool to perform pdf to txt then see what can do.
   - Crawler for violation records in MS word form.
     *Try use online tool supporting doc to txt then see what can do.

6. jobhelper
   - forum : https://groups.google.com/forum/?fromgroups#!forum/twjobhelper
   - chrome plugin : http://jobhelper.g0v.ronny.tw/
   - chrome source code : https://github.com/ronnywang/jobhelper
   - firefox plugin : https://addons.mozilla.org/zh-TW/firefox/addon/job-helper/
   - firefox source code : https://github.com/yisheng-liu/jobhelper_ff
