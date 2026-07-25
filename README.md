# Arabic Voice Chatbot 🎙️ (Gemini API)

A lightweight, browser-based voice assistant in Arabic. It listens through the mic, sends your speech to Google Gemini, and reads the answer back out loud — no frameworks, deployable on free PHP hosting (no Node.js needed).

## How it works
- **Frontend** — `index.html`, `style.css`, `app.js`: uses the browser's Web Speech API for speech-to-text and text-to-speech.
- **Backend** — `process.php`: a small PHP proxy that calls the Gemini API server-side, so the API key never reaches the browser. `config.php` (where the key lives) is blocked from direct access via `.htaccess`.

## Setup
1. Get a free key from [Google AI Studio](https://aistudio.google.com/app/apikey) and add it to `config.php`.
2. Upload all files to the **same folder** on your host: `index.html`, `style.css`, `app.js`, `process.php`, `config.php`, `.htaccess`.
3. Open the site in Chrome or Edge and tap the mic.

## The problem I hit (and how I fixed it)
The bot kept failing with a generic *"server connection error"*, with no clue why. Turned out to be a few stacked issues:

| Cause | Fix |
|---|---|
| Backend file was renamed to `process.php`, but the frontend still called the old `api/chat.php` | Pointed `app.js` to the correct filename |
| All errors were hidden behind one generic message | Made the app surface the real error text instead |
| The Gemini model (`gemini-2.0-flash`) had been deprecated | Switched to a current model |
| Browser kept serving a **cached** copy of `app.js` even after the fix was uploaded | Added a cache-busting version query (`app.js?v=2`) to the script tag |

**Lesson:** when a fix doesn't seem to change anything, check the browser cache before the code — test in a private/incognito window first.

## Tech
Vanilla JS · Web Speech API · PHP + cURL · Gemini API

##Live Website to try
[Arabic Voice Chatbot](https://nasser.free.je/TestingAI)
