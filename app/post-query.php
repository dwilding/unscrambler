<?php

require $_SERVER['APP_DIR_PACKAGES'] . '/vendor/autoload.php';
use Overtrue\Pinyin\Pinyin;

header('Content-Type: application/json');

function respond_with_failure() {
  echo json_encode([
    'success' => false
  ]);
  exit();
}

// Get request body
$request_body_json = file_get_contents('php://input');
if ($request_body_json === false) {
  respond_with_failure();
}
$request_body = json_decode($request_body_json, true);
if ($request_body === null) {
  respond_with_failure();
}

// Get query
if (!array_key_exists('query', $request_body)) {
  respond_with_failure();
}
$query = substr($request_body['query'], 0, 200);

// Prepare to call APIs
$secrets = json_decode(file_get_contents($_SERVER['APP_DIR_DATA'] . '/secrets.json'), true);
function call_gpt($temperature, $user, $system) {
  global $secrets;
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
  return $result['choices'][0]['message']['content'];
}
function call_deepl($text) {
  global $secrets;
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
  return $result['translations'][0]['text'];
}

// Generate English text
$english = call_gpt(0.3, $query, 'You are a language assistant. The user will try to express something using a mix of Chinese and English. You must rephrase the user\'s text in simple English. Do not respond with anything else; no discussion is needed. Your response must be easily understood by non-native speakers of English, so please keep the vocab and grammar as simple as possible. If the user\'s text is already in simple English, you can return the text as is.');
$english = substr($english, 0, 500);

// Translate English text
$translated = call_deepl($english);

// Convert translated text to pinyin
$pinyin1 = Pinyin::sentence($translated)->join(' ');
$pinyin2 = call_gpt(0.3, $translated . "\n" . $pinyin1, 'You are a language assistant. The user will provide Chinese text followed by a pinyin transliteration. The transliteration will not account for polyphones, so some words might be inaccurate. You must respond with an accurate transliteration. Do not respond with anything else; no discussion is needed.');
$pinyin3 = call_gpt(0.3, $translated . "\n" . $pinyin2, 'You are a language assistant. The user will provide Chinese text followed by a pinyin transliteration. You must make the word spacing and punctuation spacing of the transliteration look as natural as possible. Respond with the updated transliteration only; no discussion is needed.');

// Respond with English text and translated text
echo json_encode([
  'success' => true,
  'english' => $english,
  'translated' => $translated,
  'pinyin' => $pinyin3,
]);

?>