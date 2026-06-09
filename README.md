# CI4 HMVC Chatbot

AI-powered chatbot built with **CodeIgniter 4** using **HMVC (Hierarchical Model-View-Controller)** architecture and **Ollama** for local LLM inference.

## Features

- **HMVC Architecture** — Self-contained `Chatbot` module with its own routes, controllers, models, views, config, and libraries
- **AI-Powered Responses** — Uses Ollama (local LLM) with configurable model, temperature, and system prompt
- **Persistent Conversations** — Session-based chat history stored in MySQL
- **Real-Time UI** — AJAX chat interface with Tailwind CSS, typing indicator, and smooth scrolling
- **Context-Aware** — Full conversation history sent to the LLM for coherent multi-turn dialogue

---

## Prerequisites

- **PHP 8.2+** with `curl`, `mysqli`, `mbstring`, `json` extensions
- **MySQL 8+** (or MariaDB 10.6+)
- **Composer** 2.x
- **Ollama** running locally (see [ollama.ai](https://ollama.ai))

---

## Installation

### 1. Setup the Application

```bash
cd /var/www/html
composer create-project codeigniter4/appstarter chatbot
```

### 2. Configure Environment

Copy the example env file and edit:

```bash
cp env .env
```

`.env`:
```ini
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080'
app.indexPage = ''

database.default.hostname = localhost
database.default.database = chatbot_db
database.default.username = your_db_user
database.default.password = your_db_password
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306
```

> **Important:** `app.baseURL` must match the host and port you use to access the app (no trailing path segment). Setting it to `http://localhost/chatbot` causes CI4 to strip `/chatbot` from all URLs, breaking routing.

### 3. Create Database

```sql
CREATE DATABASE chatbot_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON chatbot_db.* TO 'your_user'@'localhost' IDENTIFIED BY 'your_password';
FLUSH PRIVILEGES;
```

### 4. Run Migrations

```bash
php spark migrate --all
```

Installs the `conversations` and `messages` tables, plus the `migrations` tracker.

### 5. (Optional) Seed Sample Data

```bash
php spark db:seed "Modules\Chatbot\Database\Seeds\ChatbotSeeder"
```

### 6. Install Ollama

```bash
# Install Ollama (Linux/macOS)
curl -fsSL https://ollama.ai/install.sh | sh

# Pull a model
ollama pull gemma:2b   # Small and fast (2B params)
# or
ollama pull llama2     # Larger, more capable (7B params)

# Verify it's running
curl http://localhost:11434/api/tags
```

### 7. Start the Server

```bash
php spark serve --port=8080
```

Open `http://localhost:8080/chatbot` in your browser.

---

## Configuration

### Chatbot Module Config

`app/Modules/Chatbot/Config/Ollama.php`:

| Setting | Default | Description |
|---------|---------|-------------|
| `$host` | `http://localhost:11434` | Ollama server URL |
| `$model` | `gemma:2b` | LLM model to use |
| `$options.temperature` | `0.7` | Response creativity (0=deterministic, 1=creative) |
| `$options.top_p` | `0.9` | Nucleus sampling threshold |
| `$systemPrompt` | *(see file)* | System prompt prepended to every conversation |

### Environment Variables (`.env`)

| Variable | Required | Description |
|----------|----------|-------------|
| `app.baseURL` | Yes | Full URL to the app root (e.g. `http://localhost:8080`) |
| `app.indexPage` | No | Set to empty `''` for clean URLs |
| `database.default.*` | Yes | MySQL connection details |

---

## Architecture

### Directory Structure

```
app/
├── Config/
│   ├── Autoload.php     # PSR-4: 'Modules\Chatbot' => APPPATH . 'Modules/Chatbot'
│   ├── Routes.php       # Main routes — loads module routes via auto-discovery
│   └── Modules.php      # Module discovery enabled with 'routes' in $aliases
│
└── Modules/
    └── Chatbot/              # Self-contained HMVC module
        ├── Config/
        │   ├── Ollama.php    # Ollama API configuration
        │   └── Routes.php    # Module routes (auto-discovered)
        ├── Controllers/
        │   └── Chatbot.php   # Main controller (index + send)
        ├── Database/
        │   ├── Migrations/   # Table creation
        │   └── Seeds/        # Sample data
        ├── Filters/
        │   └── SessionFilter.php
        ├── Libraries/
        │   └── OllamaClient.php  # cURL wrapper for Ollama API
        ├── Models/
        │   ├── ConversationModel.php
        │   └── MessageModel.php
        └── Views/
            └── chat.php      # Tailwind CSS chat UI
```

### HMVC Pattern

Each module is a self-contained unit with its own:

| Component | Location | Purpose |
|-----------|----------|---------|
| **Routes** | `Config/Routes.php` | URL routing, auto-discovered by CI4 |
| **Controller** | `Controllers/Chatbot.php` | Request handling, business logic |
| **Model** | `Models/*.php` | Database interaction |
| **View** | `Views/chat.php` | UI presentation |
| **Config** | `Config/Ollama.php` | Module-specific settings |
| **Library** | `Libraries/OllamaClient.php` | External API client |
| **Migrations** | `Database/Migrations/` | Schema versioning |

The namespace `Modules\Chatbot` is registered in `app/Config/Autoload.php`:

```php
public $psr4 = [
    APP_NAMESPACE     => APPPATH,
    'Modules\Chatbot' => APPPATH . 'Modules/Chatbot',
];
```

### Request Flow

```
Browser: GET /chatbot
  │
  ▼
CI4 Router → matches `chatbot` route
  │
  ▼
Chatbot::index()
  ├─ ensureSession() — creates session ID if needed
  ├─ loads latest conversation + messages from MySQL
  └─ renders chat.php (server-side rendered history + sendUrl)
  │
  ▼
User types message → JavaScript fetch() to POST /chatbot/send
  │
  ▼
Chatbot::send()
  ├─ validates AJAX header + message content
  ├─ saves user message to messages table
  ├─ loads full conversation history
  ├─ calls generateAIResponse($history)
  │     └─ OllamaClient::chat() → POST http://localhost:11434/api/chat
  ├─ saves bot response to messages table
  └─ returns JSON { reply: "..." }
  │
  ▼
JavaScript appends bot bubble to DOM
```

### Routes

| Method | URI | Handler | Description |
|--------|-----|---------|-------------|
| GET | `/chatbot` | `Chatbot::index` | Render chat UI |
| POST | `/chatbot/send` | `Chatbot::send` | Send message, get AI reply |

Defined in `app/Modules/Chatbot/Config/Routes.php`:

```php
$routes->group('chatbot', ['namespace' => 'Modules\Chatbot\Controllers'], static function ($routes) {
    $routes->get('/', 'Chatbot::index');
    $routes->post('send', 'Chatbot::send');
});
```

---

## API Reference

### POST `/chatbot/send`

Send a message and receive an AI-generated reply.

**Headers:**

| Header | Required | Value |
|--------|----------|-------|
| `X-Requested-With` | Yes | `XMLHttpRequest` |
| `Content-Type` | Yes | `application/x-www-form-urlencoded` or `multipart/form-data` |

**Request Body:**

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `message` | string | Yes | The user's message text |

**Success Response (200):**

```json
{
    "reply": "Hello! How can I help you today?"
}
```

**Error Responses:**

| Status | Body | Cause |
|--------|------|-------|
| `400` | `{"error": "Invalid request"}` | Missing `X-Requested-With` header |
| `400` | `{"error": "Message is required"}` | Empty or missing `message` field |

**Example with cURL:**

```bash
curl -X POST http://localhost:8080/chatbot/send \
  -d "message=Hello" \
  -H "X-Requested-With: XMLHttpRequest"
```

---

## Database Schema

### `conversations`

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT(11) UNSIGNED | Primary key, auto-increment |
| `session_id` | VARCHAR(255) | Indexed, ties to browser session |
| `title` | VARCHAR(255) | Auto-generated from first message |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | |

### `messages`

| Column | Type | Notes |
|--------|------|-------|
| `id` | INT(11) UNSIGNED | Primary key, auto-increment |
| `conversation_id` | INT(11) UNSIGNED | FK → conversations.id, CASCADE delete |
| `role` | ENUM('user', 'bot') | Message sender |
| `message` | TEXT | Message content |
| `created_at` | DATETIME | |

**Relationships:** `conversations` 1:N `messages`

---

## AI Integration (Ollama)

### How It Works

1. The controller builds a `messages` array in OpenAI-compatible format:

```
[
  { role: "system", content: "You are a helpful assistant..." },
  { role: "user",   content: "Hello" },
  { role: "assistant", content: "Hi there!" },
  { role: "user",   content: "Tell me a joke" }
]
```

2. `OllamaClient::chat()` extracts the system message, sends the rest to `POST /api/chat` on the Ollama server with `stream: false`.

3. The response is parsed and returned as a plain string.

### Configuration

Edit `app/Modules/Chatbot/Config/Ollama.php`:

```php
public string $host = 'http://localhost:11434';
public string $model = 'gemma:2b';           // Switch to 'llama2', 'mistral', etc.
public array $options = [
    'temperature' => 0.7,
    'top_p'       => 0.9,
];
public string $systemPrompt = 'You are a helpful and friendly AI assistant...';
```

### Available Models

| Model | Size | Quality | Speed |
|-------|------|---------|-------|
| `gemma:2b` | 2B params | Good | Fast |
| `deepseek-coder:1.3b` | 1.3B | Good for code | Fastest |
| `llama2` | 7B | Better | Slower |

### Custom Response Format

The `OllamaClient` can be extended to support streaming responses or different providers:

```php
// Libraries/OllamaClient.php
public function chat(array $messages): string
{
    // Override to use OpenAI, Anthropic, etc.
}
```

---

## Extending the Module

### Adding a New Module

1. Create `app/Modules/YourModule/` with the same structure
2. Register in `app/Config/Autoload.php`:
   ```php
   'Modules\YourModule' => APPPATH . 'Modules/YourModule',
   ```
3. Create `Config/Routes.php` — routes are auto-discovered
4. Create your controllers, models, views

### Changing the AI Provider

1. Create a new library class implementing the same `chat(array $messages): string` interface
2. Update `Chatbot.php` to use the new client instead of `OllamaClient`

### Adding a New Endpoint

Add to `app/Modules/Chatbot/Config/Routes.php`:

```php
$routes->get('history', 'Chatbot::history');
```

Then add the `history()` method to `Controllers/Chatbot.php`.

---

## Troubleshooting

### "Can't find a route for 'POST: send'"

**Cause:** Request URL doesn't match any route. Typically happens when:
- `app.baseURL` in `.env` contains a path segment (e.g., `http://localhost/chatbot`) causing CI4 to strip `/chatbot` from incoming requests
- Or the request is going to `http://localhost:8080/send` instead of `http://localhost:8080/chatbot/send`

**Fix:** Set `app.baseURL = 'http://localhost:8080'` (no path after the host:port). Verify with `php spark routes`.

### CORS Error in Browser Console

**Cause:** `site_url()` generates a full URL with domain (e.g., `http://localhost:8080/chatbot/send`), but the page was loaded from a different host (e.g., `http://127.0.0.1:8080/`).

**Fix:** The controller uses `parse_url(site_url('chatbot/send'), PHP_URL_PATH)` to extract only the path (`/chatbot/send`), which is origin-relative. Access the app consistently (always `localhost` or always `127.0.0.1`).

### Ollama Returns "I encountered an error"

**Check:**
- Ollama is running: `curl http://localhost:11434/api/tags`
- The model specified in `Config/Ollama.php` is pulled: `ollama list`
- PHP has `curl` extension installed: `php -m | grep curl`
- No firewall blocking port 11434

### Slow First Response

The first request loads the LLM into memory (can take 30-60s for 2B+ models). Subsequent requests are faster. To preload:

```bash
# Send a warm-up request
curl -X POST http://localhost:11434/api/chat \
  -d '{"model": "gemma:2b", "messages": [{"role": "user", "content": "hi"}]}'
```

---

## License

MIT
