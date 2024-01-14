<?php

require $_SERVER['APP_DIR_FUNCTIONS'] . '/main.php';
$secrets = json_decode(file_get_contents($_SERVER['APP_DIR_DATA'] . '/secrets.json'), true);

// For the default view (no query), show the instructions only
$dom = [
  'html_state' => '',
  'key_value' => '',
  'query_value' => '',
  'instructions_class' => 'display',
  'outputList_class' => '',
  'list_values' => []
];

$admin = FALSE;
if (array_key_exists('key', $_GET)) {
  $dom['key_value'] = htmlspecialchars($GET['key']);
  if ($GET['key'] == $secrets['keyAdmin']) {
    $admin = TRUE;
  }
}

if ($admin && array_key_exists('task', $_GET) && array_key_exists('q', $_GET)) {
  header('Cache-Control: no-cache');
  $state = [
    'sequence' => 0,
    'task' => $_GET['task'],
    'query' => $_GET['q']
  ];
  $dom['query_value'] = htmlspecialchars($state['query']);
  $dom['instructions_class'] = ''; // remove 'display' class
  if (array_key_exists('stream', $_GET) && $_GET['stream'] == 'no') {
    $state['list'] = []; // TODO
    $dom['outputList_class'] = 'display';
    foreach ($state['list'] as $item) {
      array_push($dom['list_values'], htmlspecialchars($item));
    }
  }
  $dom['html_state'] = htmlspecialchars(json_encode($state));
}

?>
<!DOCTYPE html>
<html lang="en" data-state="<?= $dom['html_state'] ?>">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="An experimental tool for people who want to improve their Chinese skills">
    <title>Unscrambler Tutor</title>
    <link rel="icon" href="/icon.svg" type="image/svg+xml" id="icon">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
    <style>
      p.action {
        text-align: right;
      }
      div {
        display: none;
      }
      div.display {
        display: block;
      }
      #loading {
        text-align: center;
        color: var(--text-light);
      }
      footer a {
        color: var(--text-light) !important;
      }
    </style>
  </head>
  <body>
    <main>
      <form action="/tutor" method="GET">
        <p class="action">
          <input type="hidden" name="key" value="<?= $dom['key_value'] ?>">
          <input type="hidden" name="stream" value="no">
          <textarea id="query" required name="q"><?= $dom['query_value'] ?></textarea>
          <button id="review" formmethod="get" formaction="/tutor?task=review">Review</button>
          <button id="explain" formmethod="get" formaction="/tutor?task=explain">Explain</button>
        </p>
      </form>
      <div id="instructions" class="<?= $dom['instructions_class'] ?>">
        <p>
          Beta
        </p>
      </div>
      <div id="outputList" class="<?= $dom['outputList_class'] ?>">
        <p>
          Output
        </p>
      </div>
    </main>
    <footer>
      <p>
        Unscrambler is an experimental language assistant developed by <a href="https://github.com/dwilding" target="_blank">Dave Wilding</a>.
        The interpretation of your input may be inaccurate.
        Don't believe everything that Unscrambler tells you!
      </p>
      <p>
        Acknowledgments:
        <a href="https://simplecss.org" target="_blank">Simple.css</a>,
        <a href="https://codepen.io/aurer" target="_blank">Aurer</a>,
        <a href="https://github.com/overtrue/pinyin" target="_blank">overtrue/pinyin</a>,
        <a href="https://www.fiverr.com/mackwhyte" target="_blank">mackwhyte</a>
      </p>
      <p>
        <a href="https://github.com/dwilding/unscrambler" target="_blank">Source code</a>
        •
        <a href="https://maybecoding.bearblog.dev/blog/" target="_blank">Dev blog</a>
      </p>
    </footer>
  </body>
</html>