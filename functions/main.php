<?php

require $_SERVER['APP_DIR_FUNCTIONS'] . '/services.php';
require $_SERVER['APP_DIR_PACKAGES'] . '/vendor/autoload.php';
use Overtrue\Pinyin\Pinyin;

function add_english(&$state, $secrets) {
  $gpt_data = call_gpt(
    $secrets,
    0.3,
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
  $pinyin = Pinyin::sentence($state['translated'])->join(' ');
  // // Pinyin might be innacurate because of polyphones. Use GPT to correct the pinyin
  // $gpt_data = call_gpt(
  //   $secrets,
  //   0.3,
  //   $state['translated'] . "\n" . $pinyin,
  //   'You are a language assistant. The user will provide Chinese text on line 1 followed by a pinyin transliteration on line 2. The transliteration will not account for polyphones, so some words might be inaccurate. You must respond with an accurate transliteration. Do not respond with anything else; no discussion is needed.'
  // );
  // $pinyin = $gpt_data['output'];
  // // Use GPT to format the pinyin (3.5 Turbo can't simultaneously correct and format pinyin)
  // $gpt_data = call_gpt(
  //   $secrets,
  //   0.3,
  //   $state['translated'] . "\n" . $pinyin, 
  //   'You are a language assistant. The user will provide Chinese text on line 1 followed by a pinyin transliteration on line 2. You must make the word spacing and punctuation spacing of the transliteration look as natural as possible. Respond with the updated transliteration only; no discussion is needed.'
  // );
  // $pinyin = $gpt_data['output'];
  $state['sequence'] = 3;
  $state['pinyin'] = '<p>' . htmlspecialchars($pinyin) . '</p>';
}

?>