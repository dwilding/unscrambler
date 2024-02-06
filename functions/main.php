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

function call_azure_break_pinyin($secrets, $text) {
  $request_data = [
    [
      'Text' => $text
    ]
  ];
  $request = curl_init('https://api-nam.cognitive.microsofttranslator.com/breaksentence?api-version=3.0&language=zh-Hans&script=Latn');
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
  return $result[0]['sentLen'];
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

function perform_unscramble($secrets, &$state) {
  $english = call_gpt(
    $secrets,
    0.3,
    $state['query'],
    'You are a language assistant. The user will try to express something using a mix of Chinese and English. You must rephrase the user\'s text in simple English. Do not respond with anything else; no discussion is needed. Your response must be easily understood by non-native speakers of English, so please keep the vocab and grammar as simple as possible. If the user\'s text is already in simple English, you can return the text as is.'
  );
  $english = mb_substr($english, 0, 500, 'UTF-8');
  $translation = call_azure_translate($secrets, $english);
  $hanzi = $translation['text'];
  $hanzi_sentences = split_sentences($hanzi, $translation['sentLen']['transSentLen']);
  $pinyin = $translation['transliteration']['text'];
  $pinyin_sentences = split_sentences($pinyin, call_azure_break_pinyin($secrets, $pinyin));
  $sentences = array_map(fn($hanzi_sentence, $pinyin_sentence) => [
    'hanzi' => $hanzi_sentence,
    'pinyin' => $pinyin_sentence
  ], $hanzi_sentences, $pinyin_sentences);
  $state['outputHTML'] = '<p><em>' . htmlspecialchars($english) . '</em></p><p><mark>' . htmlspecialchars($hanzi) . '</mark></p><article><summary>Pinyin</summary>';
  foreach ($sentences as $sentence) {
    $state['outputHTML'] .= '<p>' . htmlspecialchars($sentence['hanzi']) . '<br>' . htmlspecialchars($sentence['pinyin']) . '</p>';
  }
}

?>