<?php
define('API', 'restAPI.php');
$url = "http://localhost/bibapp_xampp/" . API;

//lege D-Klasse an
$klassenname = 'pbd2h24a';
$ical_link = 'https://intranet.bib.de/ical/d819a07653892b46b6e4d2765246b7ab';
// $ical_link = 'https://intranet.bib.de/ical/fad4c7872fcb7c42517c495fd83d99d6';
//S-Klasse
// $klassenname = 'pbs2h24s';
// $ical_link = 'https://intranet.bib.de/ical/1e2f4d3b4c5d6e7f8091a2b3c4d5e6f7';
// &$ical_link = 'https://intranet.bib.de/ical/9f8e7d6c5b4a3b2c1d0e9f8e7d6c5b4a';

$params = json_encode(array(
  "klassenname" => $klassenname,
  'ical_link' => $ical_link
));
$defaults = array(
  CURLOPT_URL => $url . '/class',
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => $params
);

$ch = curl_init();
curl_setopt_array($ch, ($defaults));
curl_exec($ch);
if (curl_error($ch)) {
  print(curl_error($ch));
}
curl_close($ch);
