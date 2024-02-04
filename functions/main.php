<?php

require $_SERVER['APP_DIR_FUNCTIONS'] . '/services.php';

function add_english(&$state, $secrets) {
  $state['sequence'] = 1;
  if ($state['query'] == '我想 stay 两个 weeks 在中国') {
    usleep(600000);
    $state['english'] = 'I want to stay in China for two weeks.';
  }
  elseif ($state['query'] == 'zhe ge city has a hen you yi si de history') {
    usleep(600000);
    $state['english'] = 'This city has a very interesting history.';
  }
  else {
    $result = call_gpt(
      $secrets,
      0.3,
      $state['query'],
      'You are a language assistant. The user will try to express something using a mix of Chinese and English. You must rephrase the user\'s text in simple English. Do not respond with anything else; no discussion is needed. Your response must be easily understood by non-native speakers of English, so please keep the vocab and grammar as simple as possible. If the user\'s text is already in simple English, you can return the text as is.'
    );
    $state['english'] = mb_substr($result, 0, 500, 'UTF-8');
  }
}

function add_translated(&$state, $secrets) {
  $state['sequence'] = 2;
  if ($state['query'] == '我想 stay 两个 weeks 在中国') {
    usleep(200000);
    $state['translated'] = '我想在中国呆两个星期。';
    $state['lengths']  = [11];
    $state['pinyin'] = 'wǒxiǎngzài zhōngguó dāi liǎnggèxīngqī。';
  }
  elseif ($state['query'] == 'zhe ge city has a hen you yi si de history') {
    usleep(200000);
    $state['translated'] = '这个城市有着非常有趣的历史。';
    $state['lengths']  = [14];
    $state['pinyin'] = 'zhège chéngshì yǒuzhe fēicháng yǒuqùde lìshǐ。';
  }
  else {
    $result = call_azure_translate($secrets, $state['english']);
    $state['translated'] = $result['text'];
    $state['lengths']  = $result['sentLen']['transSentLen'];
    $state['pinyin'] = $result['transliteration']['text'];
  }
}

function add_pinyin_html(&$state, $secrets) {
  $state['sequence'] = 3;
  $state['pinyinHTML'] = '<summary>Pinyin</summary>';
  if ($state['query'] == '我想 stay 两个 weeks 在中国') {
    usleep(200000);
    $state['pinyinHTML'] .= '<p>我想在中国呆两个星期。<br>wǒxiǎngzài zhōngguó dāi liǎnggèxīngqī。</p>';
  }
  elseif ($state['query'] == 'zhe ge city has a hen you yi si de history') {
    usleep(200000);
    $state['pinyinHTML'] .= '<p>这个城市有着非常有趣的历史。<br>zhège chéngshì yǒuzhe fēicháng yǒuqùde lìshǐ。</p>';
  }
  else {
    $result = call_azure_break_pinyin($secrets, $state['pinyin']);
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
}

function split_sentences($text, $sentence_lengths) {
  $sentences = [];
  $start = 0;
  foreach ($sentence_lengths as $length) {
    array_push($sentences, mb_substr($text, $start, $length, 'UTF-8'));
    $start += $length;
  }
  return $sentences;
}

function trim_chunks($chunks) {
  return array_map(fn($chunk) => preg_replace('/(^ +)|( +$)/u', '', $chunk), $chunks);
}

function perform_slice($secrets, $hanzi) {
  $pinyin = call_azure_get_pinyin($secrets, $hanzi);
  $hanzi_sentences = split_sentences($hanzi, call_azure_break_hanzi($secrets, $hanzi));
  $pinyin_sentences = split_sentences($pinyin, call_azure_break_pinyin($secrets, $pinyin));
  $sentences = array_map(fn($hanzi_sentence, $pinyin_sentence) => [
    'hanzi' => $hanzi_sentence,
    'pinyin' => $pinyin_sentence
  ], $hanzi_sentences, $pinyin_sentences);
  foreach ($sentences as $sentence) {
    $hanzi_chunks = trim_chunks(mb_split('/', $sentence['hanzi']));
    $pinyin_chunks = trim_chunks(mb_split('/', $sentence['pinyin']));
    if (count($hanzi_chunks) == 1) {
      // TODO: Decide how to handle this case
      continue;
    }
    $hanzi_display = implode(' / ', $hanzi_chunks);
    $combo = implode("\n", array_map(
      fn($hanzi_chunk, $pinyin_chunk) => $hanzi_chunk . ' (' . $pinyin_chunk . ')',
      $hanzi_chunks,
      $pinyin_chunks
    ));
    $combo = call_gpt(
      $secrets,
      0.3,
      $combo,
      'You are a language assistant. The user will provide a Chinese sentence that has been split into chunks, with each chunk on a separate line. You must add a concise English explanation of the meaning of each chunk, using the format "CHINESE (PINYIN) - MEANING". Do not respond with anything else; no discussion is needed.'
    );
    yield '<p>' . htmlspecialchars($combo) . '</p>';
  }
}

?>


