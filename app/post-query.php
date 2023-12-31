<?php

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

// Load secrets
$secrets = json_decode(file_get_contents($_SERVER['APP_DIR_DATA'] . '/secrets.json'), true);

// Generate English text
$openai_request_data = [
  'model' => 'gpt-3.5-turbo-1106',
  'temperature' => 0.3,
  'messages' => [
    [
      'role' => 'system',
      'content' => 'You are a language assistant. The user will try to express something using a mix of Chinese and English. You must rephrase the user\'s text in simple English. Do not respond with anything else; no discussion is needed. Your response must be easily understood by non-native speakers of English, so please keep the vocab and grammar as simple as possible. If the user\'s text is already in simple English, you can return the text as is.'
    ],
    [
      'role' => 'user',
      'content' => $query
    ]
  ]
];
$openai_request = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($openai_request, CURLOPT_POST, 1);
curl_setopt($openai_request, CURLOPT_POSTFIELDS, json_encode($openai_request_data));
curl_setopt($openai_request, CURLOPT_HTTPHEADER, [
  'Content-Type: application/json',
  'Authorization: Bearer ' . $secrets['keyOpenAI']
]);
curl_setopt($openai_request, CURLOPT_RETURNTRANSFER, true);
$openai_response = curl_exec($openai_request);
curl_close($openai_request);
$openai_result = json_decode($openai_response, true);
$english = substr($openai_result['choices'][0]['message']['content'], 0, 500);

// Translate English text
$deepl_request_data = [
  'source_lang' => 'EN',
  'target_lang' => 'ZH',
  'text' => [
    $english
  ]
];
$deepl_request = curl_init('https://api-free.deepl.com/v2/translate');
curl_setopt($deepl_request, CURLOPT_POST, 1);
curl_setopt($deepl_request, CURLOPT_POSTFIELDS, json_encode($deepl_request_data));
curl_setopt($deepl_request, CURLOPT_HTTPHEADER, [
  'Content-Type: application/json',
  'Authorization: DeepL-Auth-Key ' . $secrets['keyDeepL']
]);
curl_setopt($deepl_request, CURLOPT_RETURNTRANSFER, true);
$deepl_response = curl_exec($deepl_request);
curl_close($deepl_request);
$deepl_result = json_decode($deepl_response, true);
$translated = $deepl_result['translations'][0]['text'];

// Respond with English text and translated text
echo json_encode([
  'success' => true,
  'english' => $english,
  'translated' => $translated
]);

?>