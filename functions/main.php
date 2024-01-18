<?php

require $_SERVER['APP_DIR_FUNCTIONS'] . '/services.php';

function add_english(&$state, $secrets) {
  $result = call_gpt(
    $secrets,
    0.3,
    $state['query'],
    'You are a language assistant. The user will try to express something using a mix of Chinese and English. You must rephrase the user\'s text in simple English. Do not respond with anything else; no discussion is needed. Your response must be easily understood by non-native speakers of English, so please keep the vocab and grammar as simple as possible. If the user\'s text is already in simple English, you can return the text as is.'
  );
  $state['sequence'] = 1;
  $state['english'] = mb_substr($result, 0, 500, 'UTF-8');
}

function add_translated(&$state, $secrets) {
  $result = call_azure_translate($secrets, $state['english']);
  $state['sequence'] = 2;
  $state['translated'] = $result['text'];
  $state['lengths']  = $result['sentLen']['transSentLen'];
  $state['pinyin'] = $result['transliteration']['text'];
}

function add_pinyin_html(&$state, $secrets) {
  $result = call_azure_break($secrets, $state['pinyin']);
  $state['sequence'] = 3;
  $state['pinyinHTML'] = '<summary>Pinyin</summary>';
  $startTranslated = 0;
  $startPinyin = 0;
  for ($i = 0; $i < count($state['lengths']); $i++) {
    $sentenceTranslated = mb_substr($state['translated'], $startTranslated, $state['lengths'][$i], 'UTF-8');
    $sentencePinyin = mb_substr($state['pinyin'], $startPinyin, $result[$i], 'UTF-8');
    $state['pinyinHTML'] .= '<p>' . htmlspecialchars($sentenceTranslated) . '<br>' . htmlspecialchars($sentencePinyin) . '</p>';
    $startTranslated += $state['lengths'][$i];
    $startPinyin += $result[$i];
  }
}

?>