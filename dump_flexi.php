<?php
$c = curl_init('https://firestore.googleapis.com/v1/projects/doremi-admin/databases/(default)/documents/flexi');
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($c);
echo $res;
