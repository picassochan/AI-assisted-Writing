# AI-assisted Writing

Contributors: picassochan
Tags: ai, writing, content, openai, article generator, seo, streaming
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered article generation and SEO metadata for WordPress, using any OpenAI-compatible API with streaming, model fallback, and SEO plugin integration.

== Description

AI-assisted Writing helps you generate high-quality articles and SEO metadata using AI. Connect to any OpenAI-compatible API (OpenAI, Azure, local LLMs, etc.) and create content directly from your WordPress admin.

**Key Features**

* **OpenAI-Compatible API** — Connect to OpenAI, Azure, Ollama, LM Studio, or any compatible endpoint
* **Model Management** — Fetch available models, set primary and backup models with automatic failover
* **Daily Model Cache** — Model list is cached and auto-refreshed daily via WP-Cron
* **Per-Generation Model Selection** — Choose which model to use for each article on the Writing Assistant page
* **Template System** — Organize categories and topics with custom AI prompts
* **Streaming Generation** — Watch articles appear in real-time with SSE streaming
* **Stop Generation** — Abort article generation mid-stream with a single click
* **AI SEO Metadata** — Auto-generate SEO title, meta description, focus keywords, Open Graph tags, and URL slug
* **SEO Plugin Integration** — Detects and writes to Rank Math, Yoast SEO, or All in One SEO post meta
* **SEO Toggle** — Enable or disable AI SEO generation from settings
* **Markdown Output** — Articles are generated in pure Markdown, no HTML tags
* **Multi-language** — English and Chinese (extensible via .po/.mo files)
* **Debug Mode** — Optional debug panel for troubleshooting API requests

== Installation

1. Upload the `ai-assisted-writing` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **AI-assisted Writing > Settings** in the admin sidebar
4. Enter your API URL and API Key, then click **Test Connection**
5. Click **Fetch Models** and select your primary and backup models
6. Set up categories and topics in the **AI Templates** tab
7. Start writing with the **Writing Assistant**

== Frequently Asked Questions

= Which APIs are supported? =

Any API that follows the OpenAI chat completions format (`/v1/chat/completions`). This includes OpenAI, Azure OpenAI, Ollama, LM Studio, and many others.

= Can I use a local LLM? =

Yes. If your local LLM server provides an OpenAI-compatible API (such as Ollama or LM Studio), enter its URL (e.g. `http://localhost:11434`) as the API URL.

= How does model fallback work? =

When the primary model fails (timeout, rate limit, error), the plugin automatically retries with the backup model. You can also select a specific model per article from the Writing Assistant page.

= Which SEO plugins are supported? =

The plugin auto-detects Rank Math, Yoast SEO, and All in One SEO. Generated SEO metadata is saved directly to the detected plugin's post meta fields. If no SEO plugin is installed, the SEO fields are still available in the editor for manual reference.

= Can I disable SEO generation? =

Yes. Uncheck "Enable AI SEO metadata generation" on the Settings page. The SEO section will be hidden from the Writing Assistant.

= How does streaming work? =

The plugin uses Server-Sent Events (SSE) to stream the AI response in real-time. You can see the article being written character by character. Click the red **Stop** button at any time to abort generation.

== Changelog

= 0.1.0 =
* Initial release
* OpenAI-compatible API integration
* Template management (categories, topics, prompts)
* One-shot and stream article generation
* Auto tag generation
* Multi-language support (English, Chinese)