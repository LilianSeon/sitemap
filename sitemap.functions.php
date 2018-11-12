<?php

// Abstracted function to output formatted logging
function logger($message, $type)
{
    global $debug, $color;
    if ($color) {
        switch ($type) {
            case 0:
                //add
                echo $debug["add"] ? "\033[0;32m [+] $message \033[0m<br>" : "";
                break;
            case 1:
                //reject
                echo $debug["reject"] ? "\033[0;31m [-] $message \033[0m<br>" : "";
                break;
            case 2:
                //manipulate
                echo $debug["warn"] ? "\033[1;33m [!] $message \033[0m<br>" : "";
                break;
            case 3:
                //critical
                echo "\033[1;33m [!] $message \033[0m<br>";
                break;
        }
        return;
    }
    switch ($type) {
        case 0:
            //add
            echo $debug["add"] ? "[+] $message<br>" : "";
            break;
        case 1:
            //reject
            echo $debug["reject"] ? "31m [-] $message<br>" : "";
            break;
        case 2:
            //manipulate
            echo $debug["warn"] ? "[!] $message<br>" : "";
            break;
        case 3:
            //critical
            echo "[!] $message<br>";
            break;
    }
}

function flatten_url($url)
{
    global $real_site;
    $path = explode($real_site, $url);
    if (sizeof($path) > 2) {
        $path = $path[1];
    }else{
        $path = $url;
    }
    

    if (strpos($path, "www.") === false) {
        return $url;
    }else{
        $path2 = explode("/", $path);
        if (end($path2) == $real_site) {
            return $real_site . remove_dot_seg($path);
         }else{
            return $url;
         }
        return $real_site;
    }
}

/**
 * Remove dot segments from a URI path according to RFC3986 Section 5.2.4
 *
 * @param $path
 * @return string
 * @link http://www.ietf.org/rfc/rfc3986.txt
 */
function remove_dot_seg($path)
{
    if (strpos($path, '.') === false) {
        return $path;
    }

    $inputBuffer = $path;
    $outputStack = [];

    /**
     * 2.  While the input buffer is not empty, loop as follows:
     */
    while ($inputBuffer != '') {

        /**
         * A.  If the input buffer begins with a prefix of "../" or "./",
         *     then remove that prefix from the input buffer; otherwise,
         */
        if (strpos($inputBuffer, "./") === 0) {
            $inputBuffer = substr($inputBuffer, 2);
            continue;
        }
        if (strpos($inputBuffer, "../") === 0) {
            $inputBuffer = substr($inputBuffer, 3);
            continue;
        }

        /**
         * B.  if the input buffer begins with a prefix of "/./" or "/.",
         *     where "." is a complete path segment, then replace that
         *     prefix with "/" in the input buffer; otherwise,
         */
        if ($inputBuffer === "/.") {
            $outputStack[] = '/';
            break;
        }
        if (substr($inputBuffer, 0, 3) === "/./") {
            $inputBuffer = substr($inputBuffer, 2);
            continue;
        }

        /**
         * C.  if the input buffer begins with a prefix of "/../" or "/..",
         *     where ".." is a complete path segment, then replace that
         *     prefix with "/" in the input buffer and remove the last
         *     segment and its preceding "/" (if any) from the output
         *     buffer; otherwise,
         */
        if ($inputBuffer === "/..") {
            array_pop($outputStack);
            $outputStack[] = '/';
            break;
        }
        if (substr($inputBuffer, 0, 4) === "/../") {
            array_pop($outputStack);
            $inputBuffer = substr($inputBuffer, 3);
            continue;
        }

        /**
         * D.  if the input buffer consists only of "." or "..", then remove
         *     that from the input buffer; otherwise,
         */
        if ($inputBuffer === '.' || $inputBuffer === '..') {
            break;
        }

        /**
         * E.  move the first path segment in the input buffer to the end of
         *     the output buffer, including the initial "/" character (if
         *     any) and any subsequent characters up to, but not including,
         *     the next "/" character or the end of the input buffer.
         */
        if (($slashPos = stripos($inputBuffer, '/', 1)) === false) {
            $outputStack[] = $inputBuffer;
            break;
        } else {
            $outputStack[] = substr($inputBuffer, 0, $slashPos);
            $inputBuffer = substr($inputBuffer, $slashPos);
        }
    }

    return ltrim(implode($outputStack), "/");
}

// Check if a URL has already been scanned
function is_scanned($url)
{
    global $scanned;

    if (isset($scanned[$url])) {
        return true;
    }

    //Check if in array as dir and non-dir
    $url = ends_with($url, "/") ? substr($url, 0, -1) : $url . "/";
    if (isset($scanned[$url])) {
        return true;
    }

    return false;
}

function ends_with($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }
    return (substr($haystack, -$length) === $needle);
}

// Gets path for a relative link
// https://somewebsite.com/directory/file => https://somewebsite.com/directory/
// https://somewebsite.com/directory/subdir/ => https://somewebsite.com/directory/subdir/
function get_path($path)
{
    $path_depth = explode("/", $path);
    $len = strlen($path_depth[count($path_depth) - 1]);
    return (substr($path, 0, strlen($path) - $len));
}

//Get the root of the domain
function domain_root($href)
{
    $url_parts = explode('/', $href);
    return $url_parts[0] . '//' . $url_parts[2] . '/';
}

//The curl client is create outside of the function to avoid re-creating it for performance reasons
$curl_client = curl_init();

function get_data($url)
{
    global $curl_validate_certificate, $curl_client, $index_pdf, $crawler_user_agent, $content_type;
    $count=0;
    //Set URL
    curl_setopt($curl_client, CURLOPT_URL, $url);
    //Follow redirects and get new url, it will return the result of the transfer.
    curl_setopt($curl_client, CURLOPT_RETURNTRANSFER, 1);
    //Get headers
    curl_setopt($curl_client, CURLOPT_HEADER, 1);
    //Optionally avoid validating SSL
    curl_setopt($curl_client, CURLOPT_SSL_VERIFYPEER, $curl_validate_certificate);
    //Set user agent
    curl_setopt($curl_client, CURLOPT_USERAGENT, $crawler_user_agent);

    //Get data
    $data = curl_exec($curl_client);
    $content_type = curl_getinfo($curl_client, CURLINFO_CONTENT_TYPE);
    $GLOBALS['type'] = $content_type;
    $http_code = curl_getinfo($curl_client, CURLINFO_HTTP_CODE);
    $redirect_url = curl_getinfo($curl_client, CURLINFO_REDIRECT_URL);
    //setJSON($url, get_title($url), $content_type);
    //Scan new url, if redirect
    if ($redirect_url) {
       logger("URL is a redirect.", 1);
        if (strpos($redirect_url, '?') !== false) {
            $redirect_url = explode($redirect_url, "?")[0];
        }
        unset($url, $data);

        if (!check_blacklist($redirect_url)) {
            echo logger("Redirected URL is in blacklist", 1);

        } else {
            scan_url($redirect_url);
        }
    }

    //If content acceptable, return it. If not, `false`
    $html = ($http_code != 200 || (!stripos($content_type, "html"))) ? false : $data;

    //Additional data
    $timestamp = curl_getinfo($curl_client, CURLINFO_FILETIME);
    $modified = date('c', strtotime($timestamp));
    if (stripos($content_type, "application/pdf") !== false && $index_pdf) {
        $html = "This is a PDF";
    }
    //Return it as an array
    return array($html, $modified, (stripos($content_type, "image/") && $index_img));
}

//Try to match string against blacklist
function check_blacklist($string)
{
    global $blacklist;
    if (is_array($blacklist)) {
        foreach ($blacklist as $illegal) {
            if (fnmatch($illegal, $string)) {
                return false;
            }
        }
    }
    return true;
}

//Extract array of URLs from html document inside of `href`s
function get_links($html, $parent_url, $regexp)
{
    if (preg_match_all("/$regexp/siU", $html, $matches)) {
        if ($matches[2]) {
            $found = array_map(function ($href) use (&$parent_url) {
                global $real_site, $ignore_arguments;

                logger("Checking $href", 2);

                if (strpos($href, "#") !== false) {
                    logger("Dropping pound.", 2);
                    $href = preg_replace('/\#.*/', '', $href);
                }

                //Seperate $href from $query_string
                $query_string = '';
                if (strpos($href, '?') !== false) {
                    list($href, $query_string) = explode('?', $href);

                    //Parse &amp to not break curl client. See issue #23
                    $query_string = str_replace('&amp;', '&', $query_string);
                }
                if ($ignore_arguments) {
                    $query_string = '';
                }
                if (strpos($href, '?') !== false) {
                    echo "EFEASDEFSED";
                }

                if ((substr($href, 0, 7) != "http://") && (substr($href, 0, 8) != "https://")) {
                    // Link does not call (potentially) external page
                    if (strpos($href, ":")) {
                        logger("URL is an invalid protocol", 1);
                        return false;
                    }
                    if ($href == '/') {
                        logger("$href is domain root", 2);
                        $href = $real_site;
                    } elseif (substr($href, 0, 1) == '/') {
                        logger("$href is relative to root, convert to absolute", 2);
                        $href = domain_root($real_site) . substr($href, 1);
                    } else {
                        logger("$href is relative, convert to absolute", 2);
                        $href = get_path($parent_url) . $href;
                    }
                }
                logger("Result: $href", 2);
                if (!filter_var($href, FILTER_VALIDATE_URL)) {
                    logger("URL is not valid. Rejecting.", 1);
                    return false;
                }
                if (substr($href, 0, strlen($real_site)) != $real_site) {
                    logger("URL is not part of the target domain. Rejecting.", 1);
                    return false;
                }
                if (is_scanned($href . ($query_string ? '?' . $query_string : ''))) {
                    //logger("URL has already been scanned. Rejecting.", 1);
                    return false;
                }
                if (!check_blacklist($href)) {
                    logger("URL is blacklisted. Rejecting.", 1);
                    return false;
                }
                return flatten_url($href . ($query_string ? '?' . $query_string : ''));
            }, $matches[2]);
            return $found;
        }
    }
    logger("Found nothing", 2);
    return array();
}

function scan_url($url)
{
    global $scanned, $deferredLinks, $file_stream, $freq, $priority, $enable_modified, $enable_priority, $enable_frequency, $max_depth, $depth, $real_site, $indexed, $partOfDomain, $fileJSON, $imgDetect;
    $depth++;

    logger("Scanning $url", 2);
    if (is_scanned($url)) {
        logger("URL has already been scanned. Rejecting.", 1);
        return $depth--;
    }

        if (substr($url, 0, strlen($real_site)) != $real_site && $partOfDomain == 1) {
            logger("URL is not part of the target domain. Rejecting.", 1);
            return $depth--;
        }
    if ($url != $real_site) {
        
    if (strpos(file_get_contents($url, false), $url) !== false) {
        return $depth--;
    }
}

    if (!($depth <= $max_depth || $max_depth == 0)) {
        logger("Maximum depth exceeded. Rejecting.", 1);
        return $depth--;
    }

    //Note that URL has been scanned
    $scanned[$url] = 1;

    //Send cURL request
    list($html, $modified, $is_image) = get_data($url);

    setJSON($url, get_title($url), $GLOBALS['type']);

    if ($is_image) {
      // if img
    }

    if (!$html) {
        logger("Invalid Document. Rejecting.", 1);
        return $depth--;
    }
    if (!$enable_modified) {
        unset($modified);
    }

    if (strpos($url, "&") && strpos($url, ";") === false) {
        $url = str_replace("&", "&amp;", $url);
    }

    /*$map_row = "<url><br>";
    $map_row .= "<loc>$url</loc><br>";
    if ($enable_frequency) {
        $map_row .= "<changefreq>$freq</changefreq><br>";
    }
    if ($enable_priority) {
        $map_row .= "<priority>$priority</priority><br>";
    }
    if (!empty($modified)) {
        $map_row .= "   <lastmod>$modified</lastmod><br>";
    }
    $map_row .= "</url><br>";*/
    //fwrite($file_stream, $map_row); // writting XML
    $indexed++;
    logger("Added: " . $url . ((!empty($modified)) ? " [Modified: " . $modified . "]" : ''), 0);
    echo get_title($url);
    unset($is_image, $map_row);

    // Extract urls from <a href="??"></a>
    $ahrefs = get_links($html, $url, "<a\s[^>]*href=(\"|'??)([^\" >]*?)\\1[^>]*>(.*)<\/a>");

    // Extract urls from <frame src="??">
    $framesrc = get_links($html, $url, "<frame\s[^>]*src=(\"|'??)([^\" >]*?)\\1[^>]*>");

    if($imgDetect){
        $imgsrc = get_links($html, $url, "<img\s[^>]*src=(\"|'??)([^\" >]*?)\\1[^>]*>");
        $links = array_filter(array_merge($ahrefs, $framesrc, $imgsrc), function ($item) use (&$deferredLinks) {
        return $item && !isset($deferredLinks[$item]);
        });
    }else{
        $links = array_filter(array_merge($ahrefs, $framesrc), function ($item) use (&$deferredLinks) {
        return $item && !isset($deferredLinks[$item]);
        });
    }

    
    unset($html, $ahrefs, $framesrc);

    logger("Found urls: " . join(", ", $links), 2);

    //Note that URL has been deferred
    foreach ($links as $href) {
        if ($href) {
            $deferredLinks[$href] = 1;
        }
    }

    foreach ($links as $href) {
        if ($href) {
            var_dump($href);
            var_dump(strpos($href, $real_site));
            var_dump($deferredLinks[$href]);
            if (strpos($href, $real_site) === false && !isset($deferredLinks[$href])) {
                
                setJSON($href, get_title($url), $GLOBALS['type']);
            }
            scan_url($href);
        }
    }
    unset($url);
    $depth--;
}


/**
*
*  -Purpose: Return the title from the URL crawled.
*
* @param $url: string
* @return string
*
*****/


function get_title($url){
  global $content_type;
  // Return the HTML code from the web page.
  $arrContextOptions=array(
        "ssl"=>array(
            "verify_peer"=>true,
            "verify_peer_name"=>true,
        ),
    );  

  $html = file_get_contents($url, false, stream_context_create($arrContextOptions));
  	if (!$html) {
    	  $title = "Not able to load title data, This URL : $url is likely invalid";
        echo "<font style='color:red'>/!\  </font>".$title."<font style='color:red'> /!\</font>";
          goto Area1;
  	}
  libxml_use_internal_errors(true);
  $doc = new DOMDocument();
  $doc->loadHTMLFile($url);
  libxml_clear_errors();
  //Pick up the title between the <title> balise.
  $title = $doc->getElementsByTagName('title')->item(0);
  $pieces = explode("/",$url);
  if (empty($title->nodeValue) && stripos($url, ".pdf") !== false) { // IF the current file is of type PDF.
    $title = end($pieces);
  }elseif ($content_type == "image/jpeg" || $content_type == "image/png") {
    $title = end($pieces);
  }elseif (stripos($url, "text/html") !== false) {
    $title = $title->nodeValue;
  }else{
    $title = $title->nodeValue;
  }
  Area1:
  return utf8_decode($title); //Return title.
}


/*****

  -counter() Purpose: Return the amount of url append to the json file.

  -$i: int


*****/


$i = 'counter';
if (isset($i) && $i != 0) {
    $i = counter($i);
}else{
    $i=0;
}
function counter($i){

    $i++;
    return $i;
}


/*****

  -setJSON() Purpose: Return a JSON file with URL, Title and Type of pages.

  -$url: string
  -$title: string
  -$type: string
  -$count: int


*****/


function setJSON($urlPage, $titlePage, $typePage){

  global $url, $title, $type, $scanned, $limit, $i;
echo "<hr><hr>";
$i =  counter($i);
$fileName = $GLOBALS['fileJSON']; // Name the JSON file
 if (file_exists($fileName) && isset($fileName)) { // If file.json is not empty
    if (empty($typePage)) {
        $typePage = "Not able to load type data";
    }
   echo "<font style='color:blue;'>".$i."</font>";
   $content = array(
               "page".$i=>array(
                 "url"=>"".$urlPage."",
                 "title"=>"".htmlspecialchars($titlePage)."",
                 "type"=>"".$typePage.""
               ));
   $opt = json_encode($content, JSON_UNESCAPED_UNICODE);
   $change = str_replace("{{", "", $opt);
   $change = str_replace("}}", "}", $change);
   $change = ltrim($change, "{");
   $add = file_put_contents($fileName, $change.",", FILE_APPEND);
 }else{
    if (empty($typePage)) {
        $typePage = "Not able to load type data";
    }
 	echo "<font style='color:red;'>".$i."</font>";
   $dataArray = array(
               "page".$i =>array(
                 "url"=>"".$urlPage."",
                 "title"=>"".htmlspecialchars($titlePage)."",
                 "type"=>"".$typePage.""
               ));

  $content_json = json_encode($dataArray, JSON_UNESCAPED_UNICODE);
  $change = str_replace("}}", "}", $content_json);
  $openFile = fopen($fileName, 'w+');
  $a = fwrite($openFile, $change.",");
  fclose($openFile);
 }
   if ($i >= $limit) {
    $json = file_get_contents($GLOBALS['fileJSON']);
    $add = substr_replace($json, "}", -1);
    file_put_contents($GLOBALS['fileJSON'], "");
    file_put_contents($GLOBALS['fileJSON'], $add, FILE_APPEND);
      die('<br>Script trop long avec plus de '.$limit.' URLs !<br>'); // To prevent a too long loading time.
  }
}


function is_in_JSON(){
    global
}



// fnmatch() filler for non-POSIX systems

if (!function_exists('fnmatch')) {
    function fnmatch($pattern, $string)
    {
        return preg_match("#^" . strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.')) . "$#i", $string);
    } // end
} // end if

$version_functions = 2;