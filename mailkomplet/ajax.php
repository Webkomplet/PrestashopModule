<?php
require_once 'api_call.php';

$res = '';
if (isset($_GET['apiKey']) && !empty($_GET['apiKey']) && isset($_GET['baseCrypt']) && !empty($_GET['baseCrypt'])) {
    $res = mailkompletApiCall($_GET['apiKey'], $_GET['baseCrypt'], 'GET', 'mailingLists');
}
echo $res;