<?php

function call_gpt($secrets, $temperature, $user, $system) {
  $request_data = [
    'model' => 'gpt-3.5-turbo-0125',
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
  $state['outputHTML'] = '<p><em>' . htmlspecialchars($english) . '</em></p><p><mark>' . htmlspecialchars($hanzi) . '</mark></p>';
  $help_msg = 'I\'m trying to say something like "' . $state['query'] . '" in Chinese. I asked a translation app for help and it told me to say "' . $hanzi . '". Please can you explain this translation in a bit more detail?';
  $state['outputHTML'] .= '<p class="action"><a href="/copy?msg=' . rawurlencode($help_msg) . '" target="_blank" onclick="copy(event)">Copy AI help request</a> <a href="https://github.com/dwilding/unscrambler#how-it-works" target="_blank"><svg width="20px" height="20px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" color="currentColor" stroke-width="1.5"><path fill-rule="evenodd" clip-rule="evenodd" d="M1.25 12C1.25 6.06294 6.06294 1.25 12 1.25C17.9371 1.25 22.75 6.06294 22.75 12C22.75 17.9371 17.9371 22.75 12 22.75C6.06294 22.75 1.25 17.9371 1.25 12ZM10.3446 7.60313C10.0001 7.89541 9.75 8.34102 9.75 9.00001C9.75 9.41422 9.41421 9.75001 9 9.75001C8.58579 9.75001 8.25 9.41422 8.25 9.00001C8.25 7.90898 8.68736 7.04209 9.37414 6.45937C10.0446 5.89048 10.9119 5.625 11.75 5.625C12.5882 5.625 13.4554 5.89049 14.1259 6.45938C14.8126 7.0421 15.25 7.90899 15.25 9.00001C15.25 9.76589 15.0538 10.3495 14.7334 10.8301C14.4642 11.234 14.1143 11.5462 13.839 11.7919L13.839 11.7919L13.8385 11.7923L13.8366 11.794C13.8074 11.8201 13.779 11.8454 13.7517 11.87C13.4445 12.1464 13.213 12.3743 13.0433 12.6741C12.881 12.9609 12.75 13.3616 12.75 13.9999C12.75 14.4142 12.4142 14.7499 12 14.7499C11.5858 14.7499 11.25 14.4142 11.25 13.9999C11.25 13.1383 11.4315 12.4765 11.7379 11.9352C12.037 11.4069 12.4305 11.041 12.7483 10.755L12.8205 10.6901C13.1207 10.4204 13.3276 10.2347 13.4853 9.99803C13.6337 9.77553 13.75 9.48414 13.75 9.00001C13.75 8.34103 13.4999 7.89542 13.1554 7.60314C12.7946 7.29702 12.2868 7.125 11.75 7.125C11.2131 7.125 10.7054 7.29702 10.3446 7.60313ZM12.5675 18.5008C12.8446 18.1929 12.8196 17.7187 12.5117 17.4416C12.2038 17.1645 11.7296 17.1894 11.4525 17.4973L11.4425 17.5084C11.1654 17.8163 11.1904 18.2905 11.4983 18.5676C11.8062 18.8447 12.2804 18.8197 12.5575 18.5119L12.5675 18.5008Z" fill="currentColor"></path></svg></a></p>';
  $hanzi_sentences = split_sentences($hanzi, $translation['sentLen']['transSentLen']);
  $pinyin = $translation['transliteration']['text'];
  $pinyin_sentences = split_sentences($pinyin, call_azure_break_pinyin($secrets, $pinyin));
  $sentences = array_map(fn($hanzi_sentence, $pinyin_sentence) => [
    'hanzi' => $hanzi_sentence,
    'pinyin' => $pinyin_sentence
  ], $hanzi_sentences, $pinyin_sentences);
  $state['outputHTML'] .= '<details><summary>Pinyin</summary>';
  foreach ($sentences as $sentence) {
    $state['outputHTML'] .= '<p>' . htmlspecialchars($sentence['hanzi']) . '<br>' . htmlspecialchars($sentence['pinyin']) . '</p>';
  }
  $state['outputHTML'] .= '</details>';
  if ($state['query'] == '我想 stay 两个 weeks 在中国') {
    $state['outputHTML'] .= '<p><strong>Tip:</strong> You can also use pinyin in your input. <a href="/?stream=no&q=zhe%20ge%20city%20has%20a%20hen%20you%20yi%20si%20de%20history" onclick="demo(event)">Try another example</a></p>';
  }
}

?>
