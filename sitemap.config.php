<?php
/*
Sitemap Generator by Slava Knyazev. Further acknowledgements in the README.md file.

Website: https://www.knyz.org/
I also live on GitHub: https://github.com/knyzorg
Contact me: Slava@KNYZ.org
*/

//Make sure to use the latest revision by downloading from github: https://github.com/knyzorg/Sitemap-Generator-Crawler

/* Usage
Usage is pretty strait forward:
- Configure the crawler by editing this file.
- Select the file to which the sitemap will be saved
- Select URL to crawl
- Configure blacklists, accepts the use of wildcards (example: http://example.com/private/* and *.jpg)
- Generate sitemap
- Either send a GET request to this script or run it from the command line (refer to README file)
- Submit to Google
- Setup a CRON Job execute this script every so often

It is recommended you don't remove the above for future reference.
*/

// Default site to crawl
$site = "http://exemple.com";

//Json filename
$fileJSON = "file.json";

//Disable this if you want to add external url in json file.
$partOfDomain = 0;

// This will allow only $limit of scanned URLs, to prevent any too long loading time. It will creat the json file whatever happens.
$limit = 40;

// This set to 1 will crawl <img> balises. The loading time is increase.
$imgDetect = 0;

// Default sitemap filename
$file = "sitemap.xml";
$permissions = 0644;

// Depth of the crawl, 0 is unlimited
$max_depth = 0;

// Show changefreq
$enable_frequency = false;

// Show priority
$enable_priority = false;

// Default values for changefreq and priority
$freq = "daily";
$priority = "1";

// Add lastmod based on server response. Unreliable and disabled by default.
$enable_modified = false;

// Disable this for misconfigured, but tolerable SSL server.
//true (default), then cURL check the authenticity of the certificate. If you are not using an SSL or TLS connection, set to false.
$curl_validate_certificate = true;

// The pages will be excluded from crawl and sitemap.
// Use for exluding non-html files to increase performance and save bandwidth.
$blacklist = array(

);

// Enable this if your site do requires GET arguments to function
$ignore_arguments = true;

// Not yet implemented. See issue #19 for more information.
$index_img = true;

//Index PDFs
$index_pdf = true;

// Set the user agent for crawler
$crawler_user_agent = "Mozilla/5.0 (compatible; Sitemap Generator Crawler; +https://github.com/knyzorg/Sitemap-Generator-Crawler)";

// Header of the sitemap.xml
$xmlheader ='<?xml version="1.0" encoding="UTF-8"?>
<urlset
xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

// Optionally configure debug options
$debug = array(
    "add" => true,
    "reject" => false,
    "warn" => false
);


//Modify only if configuration version is broken
$version_config = 2;
