<?php
/* v 0.0.1
 * Этот скрипт полностью преднастроен. Убедитесь, что у скрипта есть права
 * на запись в папку где он лежит, либо укажте путь к файлу, на который у скрипта
 * есть права на запись.
 * Скрипт можно вызвать с параметром self_test=1, тогда он проверит возможность записи в файл
 * и возможность получения домена.
 * В случае возникновения любых проблем наш сапорт всегда готов помочь - обращайтесь.
 * Чтобы пользоваться, вызовите скрипт с параметром //ваши_домен/auto_domain.php?sid=[айди потока]
 * Скрипт можно переименовывать на свое усмотрение.
 * Если в ссистеме есть ваши персональные домены для редиректа, то система будет их использовать в первую очередь.
 * Если ваши домены редиректа будут заблокированы, то перенаправление будет происходить через системный домен.
 * Хорошего конверта :)
 */

define("MAIN_DOMAIN", "https://xxleads.top"); // откуда берем домен
define("HASH", "a52d0989163913654035a6f9e26d9807"); // Ваш хеш
define("CACHE_TIMEOUT", 5 * 60); // 5 минут в секундах
define("CACHE_FILE", "domain_cache.txt"); // Файл куда кешируем редиректный домен
define("FALLBACK_URL", "?self_test=1");

function fetchDomain() {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, MAIN_DOMAIN . "/get_domain/" . HASH);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);  // Таймаут в 5 секунд

    $output = curl_exec($ch);

    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return ['error' => $error_msg];
    }

    curl_close($ch);
    $response = json_decode($output, true);

    if(isset($response['domain'])) {
        return $response['domain'];
    } elseif (isset($response['error'])) {
        return ['error' => $response['error']];
    }

    return false;
}

function getDomain() {
    $domain = fetchDomain();

    if (is_array($domain) && isset($domain['error'])) {
        if (file_exists(CACHE_FILE)) {
            $cache = json_decode(file_get_contents(CACHE_FILE), true);
            return $cache['domain'];
        } else {
            return false;
        }
    } elseif ($domain) {
        $success = file_put_contents(CACHE_FILE, json_encode(['timestamp' => time(), 'domain' => $domain]));
        if ($success === false) {
            return $domain;
        }
    }

    return $domain;
}


header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Селф тест
if (isset($_GET['self_test'])) {
    // Проверка возможности записи в файл
    $fileWriteTest = @file_put_contents(CACHE_FILE, "") !== false ? "OK" : "Not OK";
    @unlink(CACHE_FILE);

    // Проверка успешности получения домена
    $domainCheck = fetchDomain();
    if (is_string($domainCheck)) {
        $domainTest = "OK";
    } elseif (is_array($domainCheck) && isset($domainCheck['error'])) {
        $domainTest = "Error: " . $domainCheck['error'];
    } else {
        $domainTest = "Not OK";
    }

    echo "File write: $fileWriteTest<br>Domain fetch: $domainTest";
    return;
}

    $domain = getDomain();
    if (!$domain) {
        header("Location: " . FALLBACK_URL);
        return;
    }

    $sid = false;
    if (isset($_GET['sid'])){
        $sid = $_GET['sid'];
    }
    if (!$sid) {
        echo "Укажите id потока! ?sid=[айди потока]";
        return;
    }

    $url = "https://$domain/go?sid=".$_GET['sid'];
    header("Location: $url");

?>
