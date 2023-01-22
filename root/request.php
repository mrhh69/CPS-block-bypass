<?php

$home = "/home/vol19_2/epizy.com/epiz_33402727/";
$pass_hash = "b422bf3b52ed4c3840004ba515e6f4a1d8f05f5059abf93a5c8e2bf459e05a9a";
$pass_algo = "sha256";

$session_cookie = '______now_thats_a_lot_of_underscores______';
$pass_cookie = '__________e_v_e_n_____m_o_r_e_______u_n_d_e_r_s_c_o_r_e_s__________';
$get_tag = 'new_session';


/* if post request password, set cookie */
if (isset($_POST['password'])) {
    $new_hash = hash($pass_algo, $_POST['password']);
    setcookie($pass_cookie, $new_hash);
    header("Location: /");
    exit;
}

/* first, check for session cookie */
if (!isset($_COOKIE[$session_cookie])) {
    if (!isset($_GET[$get_tag])) {
        /* if user is just starting out, send to homepage */
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
        header(sprintf("Location: %s", $uri));
        exit;
    }
}
else {
    $base_url = $_COOKIE[$session_cookie];
    $host = parse_url($base_url,  PHP_URL_HOST);
    if ($host === false) {die("invalid url");}
    if ($host === null) {die(sprintf("no host in url '%s'", $base_url));}

    $priveleged = false;
    /* if password hash is there */
    if (isset($_COOKIE[$pass_cookie])) {
        /* test it */
        if (valid_pass_hash($_COOKIE[$pass_cookie])) {$priveleged = true;}
    }

    if (!$priveleged) {
        /* check blocklist */
        if (is_blocked($host)) {
            do_blocked();
        }
    }
    

    $uri = $_SERVER['REQUEST_URI'];
    do_request($base_url, $uri);
    exit;
}


/* host is blocked */
function do_blocked() {
    //printf("host is blocked!<br>");// exit;
    $page_str = file_get_contents("blocked.html");
    printf("%s", $page_str);
    exit;
}

function do_request($base_url, $uri) {
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
    }
}


/* yes, I realize I'm filtering DNS, the one thing I swore to destroy, but what can you do? */
function is_blocked($host) {
    $re = "/^" . preg_quote($host) . "$/m";
    $block_txt = file_get_contents("block.txt");
    //printf("%s<br>", $re);
    $match = preg_match($re, $block_txt);
    if ($match === false) {die("preg_match error");}
    if ($match == 1) {
        /* is in blocklist */
        return true;
    }
    else {
        /* not in blocklist */
        return false;
    }
    die("idek");
}

/* check password hash against real hash */
function valid_pass_hash($test) {
    global $pass_hash;
    return $test == $pass_hash;
}

?>

<html>

<h1 style="display: inline;">Max's [alleged] HTTP Web Proxy</h1> <i style="display: inline;">(I think that's what it's called)</i><h1 style="display: inline;"> Thing</h1><br>
<i><b>[allegedly] Bypass CPS internet restrictions! It's so easy!</b></i><br>
<b>SITE IS CURRENTLY UNDER DEVELOPMENT [by an idiot] VERSION ALPHA0.0.69SIGMA</b><br>

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
