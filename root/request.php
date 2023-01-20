<?php

$session_cookie = '______now_thats_a_lot_of_underscores______';
$get_tag = 'new_session';
$base_url = "https://google.com/";
/* first, check for session cookie */
if (!isset($_COOKIE[$session_cookie])) {
    if (!isset($_GET[$get_tag])) {
        /* if user is just starting out, send to homepage */
        /* nothign here */
    }
    else {
        /* if the homepage sent the user here, set session cookie from request */
        $session_url = $_GET[$get_tag];
        $parsed = parse_url($session_url);
        if (!isset($parsed['scheme']) || !isset($parsed['host'])) {
            die("invalid URL!! return to <a href=\"maxclicker.rf.gd/\">homepage</a>");
        }

        $base_url = $parsed['scheme'] . "://" . $parsed['host'] . "/";
        $uri = $parsed['path'];
        if (!setcookie($session_cookie, $base_url)) {die("setcookie failed :(");}
        //do_request($base_url, $uri);
        header(sprintf("Location: %s", $uri));
        exit;
    }
}
else {
    $base_url = $_COOKIE[$session_cookie];
    $uri = $_SERVER['REQUEST_URI'];
    //printf("2<br>base_url:%s<br>uri:%s", $base_url, $uri);
    do_request($base_url, $uri);
    exit;
}



function do_request($base_url, $uri) {
    //https://orteil.dashnet.org/cookieclicker/
    //$base_url = "https://google.com/";
    //$base_url = "http://orteil.dashnet.org/"; /* orteil NOT ortiel (fucking typos) */
    //$base_url = "https://reddit.com/";
    //$uri = $_SERVER['REQUEST_URI'];
    $url = $base_url . $uri;

    // set up curl session
    $options = array(
        CURLOPT_RETURNTRANSFER => true,   // return web page
        CURLOPT_HEADER         => true,   // return headers in curl_exec
        CURLOPT_FOLLOWLOCATION => true,   // follow redirects
        CURLOPT_MAXREDIRS      => 10,     // stop after 10 redirects
        CURLOPT_ENCODING       => "",     // handle compressed
        CURLOPT_USERAGENT      => "proxy", // name of client
        CURLOPT_CONNECTTIMEOUT => 120,    // time-out on connect
        CURLOPT_TIMEOUT        => 120,    // time-out on response
        CURLOPT_HTTPGET        => true,
    );

    $ch = curl_init($url);
    curl_setopt_array($ch, $options);

    $content  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($code != 200) {
        printf("http response code is not 200 (either http request failed, or curl had an error)<br>");
        printf("code: %d<br>", $code);
        printf("errno:%d msg:'%s' !!!", curl_errno($ch),  curl_error("$ch"));
        curl_close($ch);
        die("curl error!!!");
    }
    else {
        $redirects = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $header_txt = substr($content, 0, $header_size);
        $body_txt = substr($content, $header_size);
        $reqs = explode("\r\n\r\n", $header_txt);
        /*
        for ($i = 0; $i < count($reqs) - 1; $i++) {
            printf("%d: '%s'<br>", strlen($reqs[$i]), $reqs[$i]);
        }
        */

        $header = $reqs[count($reqs) - 2];
        $body = $body_txt;

        $headers = explode("\r\n", $header);
        /* write out headers */
        $allowed_headers = array(
            "date"              => true,
            "expires"           => true,
            "cache-control"     => false,
            "content-type"      => true,
            "cross-origin-opener-policy-report-only" => false,
            "report-to"         => false,
            "p3p"               => true,
            "content-encoding"  => false,
            "server"            => false,
            "x-xss-protection"  => false,
            "x-frame-options"   => false,
            "set-cookie"        => true,
            "alt-svc"           => false,
            "transfer-encoding" => false,
        );
        for ($i = 1; $i < count($headers); $i++) {
            $head = explode(":", $headers[$i], 2);
            $lh = strtolower($head[0]);
            if (isset($allowed_headers[$lh])) {
                if (!$allowed_headers[$lh]) continue;
            } else continue;

            //printf("%s:%s<br>", $head[0], $head[1]);
            if (headers_sent()) die("headers already sent!");
            header(sprintf("%s:%s", $head[0], $head[1]));
        }

        echo $body;
        /*
        $fp = $_SERVER['DOCUMENT_ROOT'] . "/log.txt";
        $myfile = fopen($fp, "a") or die("Unable to open file!");
        fwrite($myfile, sprintf("%s\n", $uri));
        fclose($myfile);
        echo $content;
        */
    }
}

?>

<html>

<h1 style="display: inline;">Max's [alleged] HTTP Web Proxy</h1> <i style="display: inline;">(I think that's what it's called)</i><h1 style="display: inline;"> Thing</h1><br>
<i><b>[allegedly] Bypass CPS internet restrictions! It's so easy!</b></i><br>
<b>SITE IS CURRENTLY UNDER DEVELOPMENT (source code changing minute-by-minute)</b><br>

<br>
<form action="/request.php" method="get">
    <label for="new_session">Where would you like to go?</label><br>
    ex: <i>http://orteil.dashnet.org/cookieclicker/</i><br>
    <input type="text" id="url" name="new_session"><br>
    <input type="submit" value="take me there"/>
</form>

<br><br>
... so how it works is like when you input your url (URL MUST NOT CONTAIN GET TAGS) into the above it stores a cookie onto your browser that says the current domain connected to (orteil.dashnet.org), and then this website acts as a proxy, dynamically loading content from that domain as if maxclicker.rf.gd were, in fact, orteil.dashnet.org (in the eyes of the client, each http request is the exact same <i style="display: inline">almost</i>).<br>
TL;DR<br>
<h3>clear cookies to get back to this homepage when done</h3><br>
also protip: enable third party cookies for this to work similarly to how it does directly connected to requested url.

<br><br><br><br><br><br><br>
no room in the budget for CSS :)

</html>
