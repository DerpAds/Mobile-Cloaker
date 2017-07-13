<?php

function get_js($filename, $from_url=false) {
    $CI = &get_instance();
    $name = "$filename";
    $aliases = config_item("js_aliases_reverse");
    if ($from_url && isset($aliases[$filename])) $name = $aliases[$filename];
    $path = APPPATH."views/js/$name";
    $minified_path = APPPATH."views/js/minified/$name";
    // If minified version is available return that
    if (file_exists($minified_path)) return file_get_contents($minified_path);
    // Or else we minify ourselves
    $content = file_get_contents($path);
    $CI->load->library("CI_Minifier");
    $CI->ci_minifier->enable_obfuscator();
    $file = $CI->ci_minifier->js_packer($content);
    // Hack to prevent bad javascript concatenation
    if (!$from_url && !endsWith($file,';')) return $file.";";
    return $file;
}

function get_js_view($filename, $data = array(), $from_url=false) {
    $CI = &get_instance();
    $name = "$filename";
    $aliases = config_item("js_aliases_reverse");
    if ($from_url && isset($aliases[$filename])) $name = $aliases[$filename];
    $view_path = "js/$name";
    $html_content = $CI->load->view($view_path,$data, true);
    $dom = new DOMDocument();
    $dom->loadHTML($html_content);
    $js_scripts = $dom->getElementsByTagName('script');
    $content = '';
    foreach ($js_scripts as $script) {
        $content .= $script->nodeValue."\n";
    }

    $CI->load->library("CI_Minifier");
    $CI->ci_minifier->enable_obfuscator();
    $file = $CI->ci_minifier->js_packer($content);
    // Hack to prevent bad javascript concatenation
    if (!$from_url && !endsWith($file,';')) return $file.";";
    return $file;
}

// Depend on the url_helper (must be autoloaded)
function get_js_url($filename) {
    $aliases = config_item("js_aliases");
    if (isset($aliases[$filename])) return site_url("js/get/".$aliases[$filename]);
    return site_url("js/get/$filename");
}


function endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}