<?php

require_once dirname(__FILE__) . '/libs/sakuraCloudBandWith.php';

$Cloud = new sakuraCloudBandWith();
echo $Cloud->checkTraffic();


