<?php

require $_SERVER['APP_DIR_FUNCTIONS'] . '/main.php';
$secrets = json_decode(file_get_contents($_SERVER['APP_DIR_DATA'] . '/secrets.json'), true);

// For the default view (no query), show the instructions only
$dom = [
  'html_state' => '',
  'query_value' => '',
  'instructions_class' => 'display',
  'outputEnglish_class' => '',
  'outputTranslated_class' => '',
  'outputPinyin_class' => '',
  'english_value' => '',
  'translated_value' => '',
  'pinyin_value' => '',
  'tips_class' => ''
];

// If a query was provided, show the output
//   stream=no -> full output
//   otherwise -> query only; JS will stream in the rest
if (array_key_exists('q', $_GET)) {
  header('Cache-Control: no-cache');
  $state = [
    'sequence' => 0,
    'query' => mb_substr($_GET['q'], 0, 200, 'UTF-8')
  ];
  $dom['query_value'] = htmlspecialchars($state['query']);
  $dom['instructions_class'] = ''; // remove 'display' class
  if (array_key_exists('stream', $_GET) && $_GET['stream'] == 'no') {
    add_english($state, $secrets);
    add_translated($state, $secrets);
    add_pinyin($state, $secrets);
    $dom['outputEnglish_class'] = 'display';
    $dom['outputTranslated_class'] = 'display';
    $dom['outputPinyin_class'] = 'display';
    $dom['english_value'] = htmlspecialchars($state['english']);
    $dom['translated_value'] = htmlspecialchars($state['translated']);
    $dom['pinyin_value'] = htmlspecialchars($state['pinyin']);
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
      <div id="instructions" class="<?= $dom['instructions_class'] ?>">
        <p>
          When you click <strong>Unscramble</strong>, an AI model will generate an English version of your input.
          The English version will then be translated into Chinese.
          <a id="example" href="/?stream=no&q=我想%20stay%20两个%20weeks%20在中国">Get started with an example</a>
        </p>
        <p>
          Unscrambler uses GPT and DeepL to interpret your input.
          <a href="https://github.com/dwilding/unscrambler/#unscrambler--translate-a-mix-of-chinese-and-english" target="_blank">Learn more</a>
        </p>
      </div>
      <div id="outputEnglish" class="<?= $dom['outputEnglish_class'] ?>">
        <p>
          <em id="english"><?= $dom['english_value'] ?></em>
        </p>
      </div>
      <div id="outputTranslated" class="<?= $dom['outputTranslated_class'] ?>">
        <p>
          <mark id="translated"><?= $dom['translated_value'] ?></mark>
        </p>
      </div>
      <div id="outputPinyin" class="<?= $dom['outputPinyin_class'] ?>">
        <details>
          <summary>Pinyin</summary>
          <p id="pinyin"><?= $dom['pinyin_value'] ?></p>
        </details>
      </div>
      <div id="loading">
        <p>
          <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="30px" viewBox="0 0 24 30" style="enable-background:new 0 0 50 50;" xml:space="preserve"><rect x="0" y="13" width="4" height="5" fill="currentColor"><animate attributeName="height" attributeType="XML" values="5;21;5" begin="0s" dur="0.6s" repeatCount="indefinite" /><animate attributeName="y" attributeType="XML" values="13; 5; 13" begin="0s" dur="0.6s" repeatCount="indefinite" /></rect><rect x="10" y="13" width="4" height="5" fill="currentColor"><animate attributeName="height" attributeType="XML" values="5;21;5" begin="0.15s" dur="0.6s" repeatCount="indefinite" /><animate attributeName="y" attributeType="XML" values="13; 5; 13" begin="0.15s" dur="0.6s" repeatCount="indefinite" /></rect><rect x="20" y="13" width="4" height="5" fill="currentColor"><animate attributeName="height" attributeType="XML" values="5;21;5" begin="0.3s" dur="0.6s" repeatCount="indefinite" /><animate attributeName="y" attributeType="XML" values="13; 5; 13" begin="0.3s" dur="0.6s" repeatCount="indefinite" /></rect></svg>
        </p>
      </div>
      <div id="tips" class="<?= $dom['tips_class'] ?>">
        <p>
          <strong>Tip:</strong> You can also use pinyin in your input.
          <a id="example2" href="/?stream=no&q=zhe%20ge%20city%20has%20a%20hen%20you%20yi%20si%20de%20history">Try another example</a>
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
        <a href="https://github.com/overtrue/pinyin" target="_blank">overtrue/pinyin</a>
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
        "instructions",
        "example",
        "loading",
        "outputEnglish",
        "outputTranslated",
        "outputPinyin",
        "english",
        "translated",
        "pinyin",
        "tips",
        "example2"
      ]) {
        dom[id] = document.getElementById(id);
      }
      let stream = null;
      function streamStart() {
        dom.loading.classList.add("display");
        stream = new EventSource(`/stream-query?q=${encodeURIComponent(dom.query.value)}`);
        stream.onmessage = event => {
          const state = JSON.parse(event.data);
          if (!("sequence" in state)) {
            dom.loading.classList.remove("display");
            stream.close();
            stream = null;
            return;
          }
          switch (state.sequence) {
            case 1: {
              dom.outputEnglish.classList.add("display");
              dom.english.innerText = state.english;
              break;
            }
            case 2: {
              dom.outputTranslated.classList.add("display");
              dom.translated.innerText = state.translated;
              break;
            }
            case 3: {
              dom.outputPinyin.classList.add("display");
              dom.pinyin.innerText = state.pinyin;
            }
            default: {
              history.replaceState(state, null, `/?q=${encodeURIComponent(state.query)}`);
              dom.loading.classList.remove("display");
              if (dom.query.value == "我想 stay 两个 weeks 在中国") {
                dom.tips.classList.add("display");
              }
              else {
                dom.tips.classList.remove("display");
              }
              stream.close();
              stream = null;
            }
          }
        };
      }
      function unscramble() {
        const state = {
          sequence: 0,
          query: dom.query.value
        };
        history.pushState(state, null, `/?q=${encodeURIComponent(state.query)}`);
        dom.instructions.classList.remove("display");
        dom.outputEnglish.classList.remove("display");
        dom.outputTranslated.classList.remove("display");
        dom.outputPinyin.classList.remove("display");
        dom.tips.classList.remove("display");
        streamStart();
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
      dom.example.addEventListener("click", event => {
        event.preventDefault();
        dom.query.value = "我想 stay 两个 weeks 在中国";
        unscramble();
      });
      dom.example2.addEventListener("click", event => {
        event.preventDefault();
        if (stream === null) {
          dom.query.value = "zhe ge city has a hen you yi si de history";
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
          dom.instructions.classList.add("display");
          dom.outputEnglish.classList.remove("display");
          dom.outputTranslated.classList.remove("display");
          dom.outputPinyin.classList.remove("display");
          dom.loading.classList.remove("display");
          dom.tips.classList.remove("display");
        }
        else {
          dom.query.value = event.state.query;
          dom.instructions.classList.remove("display");
          if (event.state.sequence == 0) {
            dom.outputEnglish.classList.remove("display");
            dom.outputTranslated.classList.remove("display");
            dom.outputPinyin.classList.remove("display");
            dom.tips.classList.remove("display");
            streamStart();
          }
          else {
            dom.outputEnglish.classList.add("display");
            dom.outputTranslated.classList.add("display");
            dom.outputPinyin.classList.add("display");
            dom.english.innerText = event.state.english;
            dom.translated.innerText = event.state.translated;
            dom.pinyin.innerText = event.state.pinyin;
            dom.loading.classList.remove("display");
            if (dom.query.value == "我想 stay 两个 weeks 在中国") {
              dom.tips.classList.add("display");
            }
            else {
              dom.tips.classList.remove("display");
            }
          }
        }
      });
      if (document.documentElement.getAttribute("data-state") != "") {
        const state = JSON.parse(document.documentElement.getAttribute("data-state"));
        history.replaceState(state, null, `/?q=${encodeURIComponent(state.query)}`);
        if (state.sequence == 0) {
          streamStart();
        }
      }
    </script>
  </body>
</html>