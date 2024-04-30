<?php

$dom = [
  'msg_html' => ''
];
if (array_key_exists('msg', $_GET)) {
  $dom['msg_html'] = htmlspecialchars($_GET['msg']);
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="An experimental tool for people who want to improve their Chinese skills">
    <title>Unscrambler</title>
    <link rel="icon" href="/favicons/icon-16.png" sizes="16x16" type="image/png">
    <link rel="icon" href="/favicons/icon-32.png" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/icon-180-apple.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
  </head>
  <body>
    <main>
      <p class="notice"><?= $dom['msg_html'] ?></p>
    </main>
  </body>
</html>