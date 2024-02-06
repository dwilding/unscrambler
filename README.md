# Unscrambler — Translate a mix of Chinese and English

[Unscrambler](https://unscrambler.dpw.me) is an experimental tool for people who want to improve their Chinese skills - e.g., me!

If you're trying to express something in Chinese, but don't know all the vocab or grammar, you can type your best effort into Unscrambler and use English for the parts you don't know. Unscrambler will rephrase what you're trying to say (using simple English) and then provide a Chinese translation.

[Get started with an example](https://unscrambler.dpw.me/?q=我想%20stay%20两个%20weeks%20在中国)

The pinyin version of the Chinese translation is hidden by default, so that you can try reading the 汉字 first 😛

## How it works

 1. [GPT-3.5 Turbo](https://platform.openai.com/docs/models/gpt-3-5) generates an English version of your input.

 2. [Azure AI Translator](https://azure.microsoft.com/en-us/products/ai-services/ai-translator/) translates the English version into Chinese.