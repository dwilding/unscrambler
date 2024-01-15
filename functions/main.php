<?php

require $_SERVER['APP_DIR_FUNCTIONS'] . '/services.php';
require $_SERVER['APP_DIR_PACKAGES'] . '/vendor/autoload.php';
use Overtrue\Pinyin\Pinyin;

function add_english(&$state, $secrets) {
  $gpt_data = call_gpt(
    $secrets,
    0.3,
    false,
    $state['query'],
    'You are a language assistant. The user will try to express something using a mix of Chinese and English. You must rephrase the user\'s text in simple English. Do not respond with anything else; no discussion is needed. Your response must be easily understood by non-native speakers of English, so please keep the vocab and grammar as simple as possible. If the user\'s text is already in simple English, you can return the text as is.'
  );
  $state['sequence'] = 1;
  $state['english'] = mb_substr($gpt_data['output'], 0, 500, 'UTF-8');
}

function add_translated(&$state, $secrets) {
  $deepl_data = call_deepl($secrets, $state['english']);
  $state['sequence'] = 2;
  $state['translated'] = $deepl_data['output'];
}

function add_pinyin(&$state, $secrets) {
  $state['sequence'] = 3;
  $state['pinyin'] = '<summary>Pinyin</summary>';
  $gpt_data = call_gpt(
    $secrets,
    0.3,
    true,
    $state['translated'],
    'You are a language assistant. The user will provide Chinese text. You must split the text into individual phrases (词组). You must also remove any punctuation. Respond with a JSON object with a key called "array" that contains an array of the phrases.'
  );
  $phrases = json_decode($gpt_data['output'], true)['array'];
  foreach ($phrases as $phrase) {
    $pinyin = Pinyin::sentence($phrase)->join(' ');
    $state['pinyin'] .= '<p>' . htmlspecialchars($phrase) . '<br>' . htmlspecialchars($pinyin) . '</p>';
  }
}

?>