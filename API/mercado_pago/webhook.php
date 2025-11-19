<?php
file_put_contents("mp_webhook_log.txt", date("Y-m-d H:i:s") . " => " . file_get_contents("php://input") . "\n\n", FILE_APPEND);
http_response_code(200);
?>
