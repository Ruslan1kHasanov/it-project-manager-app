<?php

$rawdata = file_get_contents('php://input');

$data = json_decode($rawdata, JSON_UNESCAPED_UNICODE);

print_r($data);

exit();