# AI-assisted Writing

Contributors: picassochan
Tags: ai, writing, content, openai, article generator
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered article generation for WordPress using OpenAI-compatible APIs.

== Description ==

AI-assisted Writing helps you generate high-quality articles using AI. Connect to any OpenAI-compatible API (OpenAI, Azure, local LLMs, etc.) and start creating content directly in your WordPress admin.

**Features:**

* **Flexible API Configuration** - Connect to any OpenAI-compatible API with custom endpoints
* **Auto Model Detection** - Test your connection and automatically fetch available models
* **Primary & Backup Models** - Set a primary model with automatic failover to a backup
* **Template System** - Organize categories and topics with custom prompts
* **Two Generation Modes** - One-shot (full article) or step-by-step (outline then expand)
* **Auto Tag Generation** - AI suggests relevant tags for your articles
* **Multi-language Support** - English and Chinese (extensible)

== Installation ==

1. Upload the `ai-assisted-writing` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **AI-assisted Writing** in the admin sidebar
4. Enter your API URL and API Key, then click "Test Connection"
5. Select your primary and backup models
6. Set up categories and topics in the **AI Templates** tab
7. Start writing with the **Writing Assistant**

== Frequently Asked Questions ==

= Which APIs are supported? =
Any API that follows the OpenAI chat completions format (`/v1/chat/completions`). This includes OpenAI, Azure OpenAI, Ollama, LM Studio, and many others.

= Can I use a local LLM? =
Yes! If your local LLM server provides an OpenAI-compatible API (like Ollama or LM Studio), simply enter its URL (e.g. `http://localhost:11434`) as the API URL.

= How does the backup model work? =
When the primary model fails (timeout, rate limit, etc.), the plugin automatically retries with the backup model.

== Changelog ==

= 1.0.0 =
* Initial release
* OpenAI-compatible API integration
* Template management (categories, topics, prompts)
* One-shot and step-by-step article generation
* Auto tag generation
* Multi-language support (English, Chinese)

== Upgrade Notice ==

= 1.0.0 =
Initial release of AI-assisted Writing.
