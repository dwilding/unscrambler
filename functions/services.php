<?php

function call_gpt($secrets, $temperature, $expect_json, $user, $system) {
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
  if ($expect_json) {
    $request_data['response_format'] = [
      'type' => 'json_object'
    ];
  }
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
  return [
    'output' => $result['choices'][0]['message']['content'],
    'metrics' => [
      'gpt_tokens_prompt' => $result['usage']['prompt_tokens'],
      'gpt_tokens_generated' => $result['usage']['completion_tokens']
    ]
  ];
}

function call_deepl($secrets, $text) {
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
  $output = $result['translations'][0]['text'];
  return [
    'output' => $output,
    'metrics' => [
      'deepl_chars_input' => mb_strlen($text, 'UTF-8'),
      'deepl_chars_output' => mb_strlen($output, 'UTF-8')
    ]
  ];
}

?>