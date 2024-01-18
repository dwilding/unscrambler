<?php

function call_gpt($secrets, $temperature, $user, $system) {
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
    'Content-Type: application/json; charset=UTF-8',
    'Authorization: Bearer ' . $secrets['keyOpenAI']
  ]);
  curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($request);
  curl_close($request);
  $result = json_decode($response, true);
  return $result['choices'][0]['message']['content'];
}

function call_azure_translate($secrets, $text) {
  $request_data = [
    [
      'Text' => $text
    ]
  ];
  $request = curl_init('https://api-nam.cognitive.microsofttranslator.com/translate?api-version=3.0&from=en&to=zh-Hans&toScript=Latn&includeSentenceLength=true');
  curl_setopt($request, CURLOPT_POST, 1);
  curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($request_data));
  curl_setopt($request, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json; charset=UTF-8',
    'Ocp-Apim-Subscription-Region: eastus',
    'Ocp-Apim-Subscription-Key: ' . $secrets['keyAzureTranslator']
  ]);
  curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($request);
  curl_close($request);
  $result = json_decode($response, true);
  return $result[0]['translations'][0];
}

?>