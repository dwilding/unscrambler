<?php

require $_SERVER['APP_DIR_FUNCTIONS'] . '/main.php';
$secrets = json_decode(file_get_contents($_SERVER['APP_DIR_DATA'] . '/secrets.json'), true);

// For the default view (no query), show the intro only
$dom = [
  'html_state' => '',
  'query_value' => '',
  'intro_class' => 'display',
  'output_html' => ''
];

// If a query was provided, show the output
//   stream=no -> full output
//   otherwise -> query only; JS will stream in the rest
if (array_key_exists('q', $_GET)) {
  $state = [
    'query' => mb_substr($_GET['q'], 0, 200, 'UTF-8')
  ];
  $dom['query_value'] = htmlspecialchars($state['query']);
  $dom['intro_class'] = ''; // remove 'display' class
  if (array_key_exists('stream', $_GET) && $_GET['stream'] == 'no') {
    perform_unscramble($secrets, $state);
    $dom['output_html'] = $state['outputHTML'];
    if ($state['query'] == '我想 stay 两个 weeks 在中国') {
      $dom['tips_class'] = 'display';
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
    <title>Unscrambler</title>
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
      <form action="/" method="GET">
        <p class="action">
          <input type="hidden" name="stream" value="no">
          <textarea id="query" required name="q" maxlength="200" placeholder="Write a mix of Chinese and English…"><?= $dom['query_value'] ?></textarea>
          <button id="unscramble">Unscramble</button>
        </p>
      </form>
      <div id="intro" class="<?= $dom['intro_class'] ?>">
        <p>
          If you're trying to express something in Chinese, but don't know all the vocab or grammar, write your best effort then click <strong>Unscramble</strong>.
          <a href="/?stream=no&q=我想%20stay%20两个%20weeks%20在中国" onclick="demo(event, '我想 stay 两个 weeks 在中国')">Try an example</a>
        </p>
        <p>
          Unscrambler uses OpenAI and Microsoft services.
          <a href="https://github.com/dwilding/unscrambler/#unscrambler--translate-a-mix-of-chinese-and-english" target="_blank">Learn more</a>
        </p>
      </div>
      <div id="output" class="display"><?= $dom['output_html'] ?></div>
      <div id="loading">
        <p>
          <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="30px" viewBox="0 0 24 30" style="enable-background:new 0 0 50 50;" xml:space="preserve"><rect x="0" y="13" width="4" height="5" fill="currentColor"><animate attributeName="height" attributeType="XML" values="5;21;5" begin="0s" dur="0.6s" repeatCount="indefinite" /><animate attributeName="y" attributeType="XML" values="13; 5; 13" begin="0s" dur="0.6s" repeatCount="indefinite" /></rect><rect x="10" y="13" width="4" height="5" fill="currentColor"><animate attributeName="height" attributeType="XML" values="5;21;5" begin="0.15s" dur="0.6s" repeatCount="indefinite" /><animate attributeName="y" attributeType="XML" values="13; 5; 13" begin="0.15s" dur="0.6s" repeatCount="indefinite" /></rect><rect x="20" y="13" width="4" height="5" fill="currentColor"><animate attributeName="height" attributeType="XML" values="5;21;5" begin="0.3s" dur="0.6s" repeatCount="indefinite" /><animate attributeName="y" attributeType="XML" values="13; 5; 13" begin="0.3s" dur="0.6s" repeatCount="indefinite" /></rect></svg>
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
        <a href="https://www.fiverr.com/mackwhyte" target="_blank">mackwhyte</a>
      </p>
      <p>
        <a href="https://github.com/dwilding/unscrambler" target="_blank">Source code</a>
        •
        <a href="https://maybecoding.bearblog.dev/blog/" target="_blank">Dev blog</a>
      </p>
    </footer>
    <script>
      const dom = {};
      for (const id of [
        "query",
        "unscramble",
        "intro",
        "output",
        "loading"
      ]) {
        dom[id] = document.getElementById(id);
      }
      let stream = null;
      function streamStart() {
        dom.loading.classList.add("display");
        stream = new EventSource(`/stream-query?q=${encodeURIComponent(dom.query.value)}`);
        stream.onmessage = event => {
          const state = JSON.parse(event.data);
          history.replaceState(state, null, `/?q=${encodeURIComponent(state.query)}`);
          dom.output.innerHTML = state.outputHTML;
          dom.loading.classList.remove("display");
          stream.close();
          stream = null;
        };
      }
      function unscramble() {
        const state = {
          query: dom.query.value
        };
        history.pushState(state, null, `/?q=${encodeURIComponent(state.query)}`);
        dom.intro.classList.remove("display");
        dom.output.innerHTML = "";
        streamStart();
      }
      function demo(event, query) {
        if (!event.ctrlKey && !event.metaKey && !event.shiftKey) {
          event.preventDefault();
          dom.query.value = query;
          unscramble();
        }
      }
      dom.query.addEventListener("keydown", event => {
        if ("key" in event && event.key.toLowerCase() == "enter") {
          event.preventDefault();
          if (dom.query.value.trim() != "" && stream === null) {
            unscramble();
          }
        }
      });
      dom.unscramble.addEventListener("click", event => {
        event.preventDefault();
        if (dom.query.value.trim() != "" && stream === null) {
          unscramble();
        }
      });
      window.addEventListener("popstate", event => {
        if (stream !== null) {
          stream.close();
          stream = null;
        }
        if (event.state === null) {
          dom.query.value = "";
          dom.intro.classList.add("display");
          dom.output.innerHTML = "";
          dom.loading.classList.remove("display");
        }
        else {
          dom.query.value = event.state.query;
          dom.intro.classList.remove("display");
          if (!("outputHTML" in event.state)) {
            dom.output.innerHTML = "";
            streamStart();
          }
          else {
            dom.output.innerHTML = event.state.outputHTML;
            dom.loading.classList.remove("display");
          }
        }
      });
      if (document.documentElement.getAttribute("data-state") != "") {
        const state = JSON.parse(document.documentElement.getAttribute("data-state"));
        history.replaceState(state, null, `/?q=${encodeURIComponent(state.query)}`);
        if (!("outputHTML" in state)) {
          streamStart();
        }
      }
    </script>
  </body>
</html>