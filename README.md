Crawlers set for jobhelper by ronnywang
==================

Set of crawlers for jobhelper.

1. Support Status
   <table>
      <tr>
         <th>City</th>
         <th>Link</th>
         <th>Status</th>
      </tr>
      <tr>
         <td>Taipei</td>
         <td>http://www.bola.taipei.gov.tw/ct.asp?xItem=41223990&ctNode=62846&mp=116003</td>
         <td>Page to CSV</td>
      </tr>
      <tr>
         <td>Keelung</td>
         <td>http://www.klcg.gov.tw/social/home.jsp?contlink=ap/news_view.jsp&dataserno=201111220003</td>
         <td>Download document</td>
      </tr>
      <tr>
         <td>New Taipei</td>
         <td>http://www.labor.ntpc.gov.tw/_file/1075/SG/46207/D.html</td>
         <td>Download document</td>
      </tr>
   </table>

2. Required package
   - php5
   - php5_curl

3. How to run
- php crawler.php

  works on linux only because of encoding issues.

4. Features
   - Check if data has been updated before parsing.
   - Export crawled data in CSV form.
   - Backup crawled pages.
   - Take snapshot of web page via a handy site I found.

      http://www.hiqpdf.com/demo/ConvertHtmlToImage.aspx

5. TODO list
   - Crawler for violation records in html form.
   - Crawler for violation records in pdf form.

      Try use tool to perform pdf to txt then see what can do.
   - Crawler for violation records in MS word form.

      Try use online tool supporting doc to txt then see what can do.

6. jobhelper
   - forum : https://groups.google.com/forum/?fromgroups#!forum/twjobhelper
   - chrome plugin : http://jobhelper.g0v.ronny.tw/
   - chrome source code : https://github.com/ronnywang/jobhelper
   - firefox plugin : https://addons.mozilla.org/zh-TW/firefox/addon/job-helper/
   - firefox source code : https://github.com/yisheng-liu/jobhelper_ff
