<?php

require $_SERVER['APP_DIR_PACKAGES'] . '/vendor/autoload.php';
use Overtrue\Pinyin\Pinyin;

header('Content-Type: text/event-stream');

function respond_with_failure() {
  echo 'data: ' . json_encode([
    'success' => false
  ]) . "\n\n";
  ob_flush();
  exit();
}

// Get query
if (!array_key_exists('q', $_GET)) {
  respond_with_failure();
}
$query = mb_substr($_GET['q'], 0, 200, 'UTF-8');

// Prepare to call APIs
$secrets = json_decode(file_get_contents($_SERVER['APP_DIR_DATA'] . '/secrets.json'), true);
$metrics = [
  'gpt_tokens_prompt' => 0,
  'gpt_tokens_generated' => 0,
  'deepl_chars_input' => 0,
  'deepl_chars_output' => 0 
];
function call_gpt($temperature, $user, $system) {
  global $secrets, $metrics;
  $request_data = [
    'model' => 'gpt-3.5-turbo-1106',
    'temperature' => $temperature,
    'messages' => [
      [
        'role' => 'system',
        'content' => $system
      ],
      [
        'role' => 'user',
        'content' => $user
      ]
    ]
  ];
  $request = curl_init('https://api.openai.com/v1/chat/completions');
  curl_setopt($request, CURLOPT_POST, 1);
  curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($request_data));
  curl_setopt($request, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $secrets['keyOpenAI']
  ]);
  curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($request);
  curl_close($request);
  $result = json_decode($response, true);
  $metrics['gpt_tokens_prompt'] += $result['usage']['prompt_tokens'];
  $metrics['gpt_tokens_generated'] += $result['usage']['completion_tokens'];
  return $result['choices'][0]['message']['content'];
}
function call_deepl($text) {
  global $secrets, $metrics;
  $request_data = [
    'source_lang' => 'EN',
    'target_lang' => 'ZH',
    'text' => [
      $text
    ]
  ];
  $request = curl_init('https://api-free.deepl.com/v2/translate');
  curl_setopt($request, CURLOPT_POST, 1);
  curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($request_data));
  curl_setopt($request, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: DeepL-Auth-Key ' . $secrets['keyDeepL']
  ]);
  curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($request);
  curl_close($request);
  $result = json_decode($response, true);
  $translated = $result['translations'][0]['text'];
  $metrics['deepl_chars_input'] += mb_strlen($text, 'UTF-8');
  $metrics['deepl_chars_output'] += mb_strlen($translated, 'UTF-8');
  return $translated;
}

// Generate English text
$english = call_gpt(0.3, $query, 'You are a language assistant. The user will try to express something using a mix of Chinese and English. You must rephrase the user\'s text in simple English. Do not respond with anything else; no discussion is needed. Your response must be easily understood by non-native speakers of English, so please keep the vocab and grammar as simple as possible. If the user\'s text is already in simple English, you can return the text as is.');
$english = mb_substr($english, 0, 500, 'UTF-8');

// Respond with English text
echo 'data: ' . json_encode([
  'success' => true,
  'sequence' => 0,
  'english' => $english
]) . "\n\n";
ob_flush();

// Translate English text
$translated = call_deepl($english);

// Respond with translated text
echo 'data: ' . json_encode([
  'success' => true,
  'sequence' => 1,
  'english' => $english,
  'translated' => $translated
]) . "\n\n";
ob_flush();

// Convert translated text to pinyin
$pinyin1 = Pinyin::sentence($translated)->join(' ');
$pinyin2 = call_gpt(0.3, $translated . "\n" . $pinyin1, 'You are a language assistant. The user will provide Chinese text on line 1 followed by a pinyin transliteration on line 2. The transliteration will not account for polyphones, so some words might be inaccurate. You must respond with an accurate transliteration. Do not respond with anything else; no discussion is needed.');
$pinyin3 = call_gpt(0.3, $translated . "\n" . $pinyin2, 'You are a language assistant. The user will provide Chinese text on line 1 followed by a pinyin transliteration on line 2. You must make the word spacing and punctuation spacing of the transliteration look as natural as possible. Respond with the updated transliteration only; no discussion is needed.');

// Respond with pinyin and usage metrics
echo 'data: ' . json_encode([
  'success' => true,
  'sequence' => 2,
  'english' => $english,
  'translated' => $translated,
  'pinyin' => $pinyin3,
  'metrics' => $metrics
]) . "\n\n";
ob_flush();

?>