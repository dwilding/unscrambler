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

// Get DeepL key
if (!array_key_exists('key', $request_body)) {
  respond_with_failure();
}
$key = $request_body['key'];

// Get DeepL endpoint
if (!array_key_exists('endpoint', $request_body)) {
  respond_with_failure();
}
$endpoint = $request_body['endpoint'];
if ($endpoint != 'api.deepl.com' && $endpoint != 'api-free.deepl.com') {
  respond_with_failure();
}

// Get English text
if (!array_key_exists('text', $request_body)) {
  respond_with_failure();
}
$text = $request_body['text'];

// Translate English text
$deepl_request_data = [
  'source_lang' => 'EN',
  'target_lang' => 'ZH',
  'text' => [
    $text
  ]
];
$deepl_request = curl_init('https://' . $endpoint . '/v2/translate');
curl_setopt($deepl_request, CURLOPT_POST, 1);
curl_setopt($deepl_request, CURLOPT_POSTFIELDS, json_encode($deepl_request_data));
curl_setopt($deepl_request, CURLOPT_HTTPHEADER, [
  'Content-Type: application/json',
  'Authorization: DeepL-Auth-Key ' . $key
]);
curl_setopt($deepl_request, CURLOPT_RETURNTRANSFER, true);
$deepl_response = curl_exec($deepl_request);
curl_close($deepl_request);
$deepl_result = json_decode($deepl_response, true);

// Respond with translated text
echo json_encode([
  'success' => true,
  'text' => $deepl_result['translations'][0]['text']
]);

?>