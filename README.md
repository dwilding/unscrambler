# Unscrambler — Translate a mix of Chinese and English

[Unscrambler](https://unscrambler.dpw.me) is an experimental tool for people who are trying to improve their Chinese skills - e.g., me!

If you're trying to express something in Chinese, but don't know all the vocabulary or grammar, you can type your best effort into Unscrambler and use English for the parts you don't know. Unscrambler will rephrase what you're trying to say (using simple English) and then provide a Chinese translation.

[Get started with an example](https://unscrambler.dpw.me/#zh/%E4%BD%A0%E6%98%AF%E4%B8%8D%E6%98%AF%20talking%20about%20%E6%98%A5%E8%8A%82%E7%9A%84%20traditions?)

The pinyin version of the Chinese translation is hidden by default, so that you can try reading the 汉字 first 😛

## How it works

 1. [GPT-3.5 Turbo](https://platform.openai.com/docs/models/gpt-3-5) generates the English version of your input.

 2. [DeepL](https://www.deepl.com/translator) translates the English version into Chinese (no pinyin).

 3. [overtrue/pinyin](https://github.com/overtrue/pinyin) generates an initial pinyin version of the Chinese translation.
    Because of polyphones such as 行, the initial pinyin version might not be 100% accurate.

 4. GPT-3.5 Turbo corrects and formats the pinyin version of the Chinese translation.