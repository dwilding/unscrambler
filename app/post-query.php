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
$query = $request_body['query'];

// Load secrets
$secrets = json_decode(file_get_contents($_SERVER['APP_DIR_DATA'] . '/secrets.json'), true);

// Generate English text
$english = 'I am a banana'; // TODO implement this properly

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

// Respond with English text and translated text
echo json_encode([
  'success' => true,
  'english' => $english,
  'translated' => $deepl_result['translations'][0]['text']
]);

?>