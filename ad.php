<?php
    $queryString = $_SERVER['QUERY_STRING'];
    $ampIndex = strpos($queryString, "&");
    if ($ampIndex !== false) {
        $campaignID = substr($queryString, 0, $ampIndex);
        $queryString = substr($queryString, $ampIndex + 1);
    } else {
        $campaignID = $queryString;
        $queryString = "";
    }
    header( "Location: public/ad/view/".$campaignID."?".$queryString);
    exit();
