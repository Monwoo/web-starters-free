<?php
// 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// ex (can't past in browser url, use curl or other tools) :
// http://localhost:8010/b64ToImg.php?b64=data%3Aimage%2Fjpeg%3Bbase64%2CiVBORw0KGgoAAAANS...(trimed)
// BUT : cpanel server will refuse the request if GET param is too long, will need post ways...
$base64strImg = $_POST['b64'] ?? $_GET['b64'] ?? null;
$type = 'jpeg';
// echo $base64strImg; exit;
// echo substr($base64strImg, -70); exit;

// $resultimg = str_replace("data:image/$type;base64,", "", $base64strImg);
$resultImg = substr($base64strImg ?? '', strlen("data:image/$type;base64,"));

// echo $resultImg; exit;

header("Content-Disposition: attachment;filename=\"image.$type\"");
header("Content-Type: image/$type");

$img = base64_decode($resultImg);
// printf("%s", $img);
echo $img;

ob_flush();