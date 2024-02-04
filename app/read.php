<?php

require $_SERVER['APP_DIR_FUNCTIONS'] . '/main.php';
$secrets = json_decode(file_get_contents($_SERVER['APP_DIR_DATA'] . '/secrets.json'), true);

$dom = [
  'html_state' => '',
  'query_value' => '',
  'intro_class' => 'display',
  'ready_class' => '',
  'output_html' => ''
];
if (array_key_exists('query', $_GET)) {
  $state = [
    'phase' => 'stream',
    'query' => mb_substr($_GET['query'], 0, 200, 'UTF-8'),
    'outputHTML' => ''
  ];
  $dom['query_value'] = htmlspecialchars($state['query']);
  $dom['intro_class'] = ''; // remove 'display' class
  if (array_key_exists('phase', $_GET)) {
    if ($_GET['phase'] == 'end') {
      $state['phase'] = 'end';
      foreach (perform_slice($secrets, $state['query']) as $outputHTML) {
        $state['outputHTML'] .= $outputHTML;
      }
      $dom['output_html'] = $state['outputHTML'];
    }
    elseif ($_GET['phase'] == 'ready') {
      $state['phase'] = 'ready';
      $dom['ready_class'] = 'display';
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
    <title>Read Chinese - Unscrambler</title>
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
      <form action="/read" method="GET">
        <p class="action">
          <input type="hidden" name="phase" value="end">
          <textarea id="query" required name="query" maxlength="200" placeholder="Placeholder TODO"><?= $dom['query_value'] ?></textarea>
          <button id="slice">Slice</button>
        </p>
      </form>
      <div id="intro" class="<?= $dom['intro_class'] ?>">
        <p>
          Instructions TODO
        </p>
      </div>
      <div id="ready" class="<?= $dom['ready_class'] ?>">
        <p>
          Put slashes (/) between chunks, then click <strong>Slice</strong> to translate each chunk.
        </p>
      </div>
      <div id="output" class="display"><?= $dom['output_html'] ?></div>
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
        <a href="https://www.fiverr.com/mackwhyte" target="_blank">mackwhyte</a>
      </p>
      <p>
        <a href="https://github.com/dwilding/unscrambler" target="_blank">Source code</a>
        •
        <a href="https://maybecoding.bearblog.dev/blog/" target="_blank">Dev blog</a>
      </p>
    </footer>
    <script>
    </script>
  </body>
</html>