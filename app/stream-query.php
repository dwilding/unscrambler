<?php

require $_SERVER['APP_DIR_FUNCTIONS'] . '/main.php';
$secrets = json_decode(file_get_contents($_SERVER['APP_DIR_DATA'] . '/secrets.json'), true);

header('Content-Type: text/event-stream');

if (!array_key_exists('q', $_GET)) {
  echo "data: {} \n\n";
  ob_flush();
  exit();
}
$state = [
  'sequence' => 0,
  'query' => mb_substr($_GET['q'], 0, 200, 'UTF-8')
];
add_english($state, $secrets);
echo 'data: ' . json_encode($state) . "\n\n";
ob_flush();
add_translated($state, $secrets);
echo 'data: ' . json_encode($state) . "\n\n";
ob_flush();
add_pinyin($state, $secrets);
echo 'data: ' . json_encode($state) . "\n\n";
ob_flush();

?>