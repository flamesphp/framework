# FlamesPHP Framework — AI Developer Instructions

This document describes **every class and method in this repository** (`flamesphp/framework`). It is intended for developers using AI-assisted coding (vibecode) so the model can understand how the framework works, what to extend, and what to avoid.

> **Important:** Classes under `Flames/(deprecated)/` are legacy code kept for reference and backward compatibility. Prefer the active classes under `Flames/` and `Flames/Framework/` for new server-side work.

---

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Request Lifecycle](#request-lifecycle)
3. [Directory Layout](#directory-layout)
4. [Active Classes (Current API)](#active-classes-current-api)
5. [Deprecated Classes (Legacy API)](#deprecated-classes-legacy-api)
6. [Conventions for AI Agents](#conventions-for-ai-agents)

---

## Architecture Overview

Flames is a **cross-side PHP framework** (server + client via WebAssembly). This package (`flamesphp/framework`) contains the **server kernel** and shared utilities.

| Concept | Description |
|---------|-------------|
| **Kernel** | Bootstraps paths, autoloading, and environment |
| **Boot** | Registers error handlers, timezone, routes, and runs dispatch |
| **Dispatch** | Matches routes, builds `RequestData`, calls controllers, sends response |
| **Event** | Resolves and invokes app event classes from `App/Server/Event/` |
| **Microservice** | Switches context between `App/` and named `Microservice/{Name}/` apps |
| **Queue** | Database-backed job queue using PDO |
| **Mesh (Twig)** | Template engine used by `Flames\Framework\View` |

### Key Constants (defined at boot)

| Constant | Value | Meaning |
|----------|-------|---------|
| `ROOT_PATH` | Project root (4 levels up from `Flames/Kernel.php`) | Application root |
| `FLAMES_PATH` | `ROOT_PATH . 'vendor/flamesphp/'` | Vendor packages path |
| `APP_PATH` | `ROOT_PATH . 'App/'` | Default application path |
| `MODULE` | `'SERVER'` | Current runtime module |
| `START_TIME` | `microtime(true)` | Boot timestamp |

---

## Request Lifecycle

```
index.php
  └─ Flames\Kernel::boot($run = true)
       └─ Kernel::setup()
            ├─ hookPaths()      → defines ROOT_PATH, APP_PATH, etc.
            ├─ autoload()       → loads Autoload::run()
            └─ Env::reload()
       └─ Boot::register($run)
            ├─ registerDate()
            ├─ registerDumpper()
            ├─ registerErrorHandler()
            ├─ registerRoute()  → Event::dispatch(RouteContract, 'Route', 'onRoute')
            └─ Boot::run()      → Dispatch::run()
                 └─ Dispatch::dispatch()
                      ├─ Event: Initialize::onInitialize()
                      ├─ Event: Route::onRoute()
                      ├─ Router::getMatch()
                      ├─ RequestMount::mountRequestData()
                      ├─ Event: Route::onMatch($requestData)
                      ├─ Controller::onRequest($requestData)
                      ├─ buildResponse() → optional Mesh/Twig render
                      ├─ Event: Output::onOutput($requestData, $output)
                      └─ Header::send() + echo output
```

---

## Directory Layout

```
framework/
├── index.php              # Entry point (requires Kernel.php)
├── App.php                # App namespace helper
├── Flames/
│   ├── Kernel.php         # Server kernel bootstrap
│   ├── Microservice.php   # Multi-app context switching
│   ├── Queue.php          # Abstract queue worker
│   ├── Queue/             # Queue attributes and reflection cache
│   ├── Framework/         # Active server framework classes
│   │   ├── Boot.php
│   │   ├── Cache.php
│   │   ├── Connection.php
│   │   ├── Dispatch.php
│   │   ├── Event.php
│   │   ├── Header.php
│   │   ├── PersistentData.php
│   │   ├── Shutdown.php
│   │   ├── View.php
│   │   └── Controller/    # Request/response layer
│   └── (deprecated)/      # Legacy client/server code
```

---

## Active Classes (Current API)

### `App\App`

Application namespace helper. Uses the `MODULE` constant defined at kernel boot.

| Method | Signature | How it works |
|--------|-----------|--------------|
| `isServer()` | `public static function isServer(): bool` | Returns `true` when `MODULE === 'SERVER'`. Used to detect server-side runtime vs client-side. |

**Property:** `public const MODULE = MODULE` — mirrors the global `MODULE` constant.

---

### `Flames\Kernel`

**Final class.** Main server bootstrap. Marked `@internal`.

| Constant / Property | Description |
|---------------------|-------------|
| `VERSION` | Framework version string (`'1.0.0'`) |
| `$errorHandler` | Static reference to `Errors\Run` error handler instance |

| Method | Signature | How it works |
|--------|-----------|--------------|
| `boot()` | `public static function boot(bool $run = false): void` | Calls `setup()`. If setup succeeds, delegates to `Boot::register($run)`. When `$run === true`, the full request dispatch cycle starts. |
| `setup()` | `public static function setup(): bool` | Runs `hookPaths()`, then `autoload()`. On success, reloads environment via `Env::reload()` and returns `true`. |
| `hookPaths()` | `protected static function hookPaths(): void` | Defines `START_TIME`, `ROOT_PATH`, `FLAMES_PATH`, `APP_PATH`, and `MODULE`. |
| `autoload()` | `protected static function autoload(): bool` | Requires and runs `Autoload::run()` from the `flamesphp/autoload` package. Returns `false` on failure. |

---

### `Flames\Microservice`

**Final class.** Manages which application context is active. Default is `App` (maps to `App/` directory and `App\` namespace).

| Method | Signature | How it works |
|--------|-----------|--------------|
| `resolve()` | `public static function resolve(): void` | Reads `config.yml` microservices section. Matches current HTTP host against configured host patterns (supports wildcards via `fnmatch`). Skipped in CLI. Activates the first matching microservice. |
| `set()` | `public static function set(string $name): void` | Programmatically activates a microservice by name. Pass `'App'` to revert to default. |
| `get()` | `public static function get(): string` | Returns the active microservice name (default: `'App'`). |
| `isDefault()` | `public static function isDefault(): bool` | Returns `true` when the default `App` context is active. |
| `getPath()` | `public static function getPath(): string` | Returns filesystem base path. `'App'` → `APP_PATH`; named → `ROOT_PATH . 'Microservice/{Name}/'`. |
| `getNamespace()` | `public static function getNamespace(): string` | Returns PHP namespace prefix with trailing backslash. `'App'` → `'App\'`; named → `'Microservice\{Name}\'`. |
| `activate()` | `protected static function activate(string $name): void` | Sets `$current` and enables `AutoLoad::$event` if the microservice has a `Server/Event/Load.php` file. |

**Configuration example (`.env`):**
```
APP_MICROSERVICES=Admin,Painel,Landing
APP_MICROSERVICES_ADMIN=admin.domain.com,admin.domain.local
```

---

### `Flames\Queue` (abstract)

Database-backed job queue. Extend this class and override `onMessage()` to process jobs.

**Attributes supported on the queue class:**

| Attribute | Class | Default | Purpose |
|-----------|-------|---------|---------|
| Database | `Flames\Orm\Attribute\Database` | auto | ORM database connection name |
| Table | `Flames\Orm\Attribute\Table` | auto-generated from class name | Queue table name |
| Delay | `Flames\Queue\Delay` | `0.1` seconds | Sleep between poll iterations |
| Timeout | `Flames\Queue\Timeout` | `300` seconds | Stale job reclaim timeout |
| TimeLimit | `Flames\Queue\TimeLimit` | `600` seconds | Max worker runtime |

| Method | Signature | How it works |
|--------|-----------|--------------|
| `add()` | `public static function add(mixed $data): void` | Serializes `$data` and inserts a row into the queue table with current timestamp. Requires PDO connection. |
| `run()` | `public static function run(): void` | Starts the worker loop. Claims jobs, calls `onMessage($id, $data)`, releases the job. Runs until time limit is reached. |
| `removeById()` | `public static function removeById(int $id): void` | Deletes a queue row by ID. |
| `onMessage()` | `public static function onMessage(int $id, mixed $data): void` | **Override this.** Called when a job is claimed. Default is empty. |
| `__constructStatic()` | `public static function __constructStatic(): void` | Loads queue config via `Queue\Data::mountData()` (cached reflection). Called automatically. |

**Worker loop behavior (`_onLoop`):**
1. Generates a unique `process_id` from server ID + PID.
2. Claims the next available job (unclaimed or timed-out).
3. Unserializes data and calls `onMessage()`.
4. Clears `process_id` on the row.
5. Sleeps for `delay` seconds until `timelimit` is reached.

---

### `Flames\Queue\Data`

**Internal.** Reflection cache for queue class configuration.

| Method | Signature | How it works |
|--------|-----------|--------------|
| `mountData()` | `public static function mountData(string $class): Arr` | Reads class attributes and caches result in `.tmp/queue/{sha1(class)}`. Invalidates when source file mtime changes. |
| `__getReflection()` | `private static function __getReflection(string $class): Arr` | Parses `Database`, `Table`, `Delay`, `Timeout`, `TimeLimit` attributes via reflection. Auto-generates table name if not set. |

---

### `Flames\Queue\Delay`

**PHP Attribute.** Marks queue delay configuration on a class.

```php
#[Delay(delay: 0.5)]
class MyQueue extends Queue {}
```

---

### `Flames\Queue\Timeout`

**PHP Attribute.** Marks stale-job timeout (seconds) on a queue class.

```php
#[Timeout(timeout: 600)]
class MyQueue extends Queue {}
```

---

### `Flames\Queue\TimeLimit`

**PHP Attribute.** Marks maximum worker runtime (seconds) on a queue class.

```php
#[TimeLimit(timelimit: 3600)]
class MyQueue extends Queue {}
```

---

### `Flames\Framework\Boot`

**Internal.** Registers framework services and optionally runs dispatch.

| Property | Description |
|----------|-------------|
| `$errorHandler` | Static `Errors\Run` instance for exception rendering |

| Method | Signature | How it works |
|--------|-----------|--------------|
| `register()` | `public static function register(bool $run = false)` | Registers date timezone, dumpper helpers, error handler, and route event. Calls `run()` when `$run === true`. |
| `run()` | `public static function run(): void` | Calls `Dispatch::run()`. Catches exceptions and renders them via `renderException()`. |
| `renderException()` | `public static function renderException(\Throwable $e, ?\Throwable $handlerError = null): void` | Uses the registered error handler (PrettyPage in web, PlainText in CLI) to render the exception. Falls back to plain text. |
| `fallbackException()` | `public static function fallbackException(\Throwable $e, ?\Throwable $handlerError = null): void` | Sends HTTP 500 with plain-text exception details and stack trace. |
| `registerWebHandlers()` | `public static function registerWebHandlers(): void` | Registers dumpper and error handler only (no full boot). |
| `registerErrorHandler()` | `protected static function registerErrorHandler(): void` | Creates `Errors\Run` with PrettyPageHandler (web) or PlainTextHandler (CLI). Controlled by `ERROR_HANDLER_ENABLED` env. |
| `registerDumpper()` | `protected static function registerDumpper(): void` | Loads global helper functions from `autoload/resources/functions.php`. |
| `registerDate()` | `protected static function registerDate(): void` | Sets timezone from `DATE_TIMEZONE` env, defaults to UTC. |
| `registerRoute()` | `protected static function registerRoute()` | Dispatches `RouteContract::onRoute` event to register application routes. |

---

### `Flames\Framework\Dispatch`

**Internal.** Core HTTP request dispatcher.

| Method | Signature | How it works |
|--------|-----------|--------------|
| `run()` | `public static function run(): bool` | Alias for `dispatch()`. |
| `dispatch()` | `public static function dispatch(): bool` | Full dispatch pipeline: Initialize event → Route event → route match → controller call → response. Returns `false` if any step fails. |
| `dispatchRoute()` | `protected static function dispatchRoute(RouteMatch $match): bool` | Mounts request data, checks `onMatch` event, instantiates controller, calls `onRequest()`, builds response, fires Output event, sends headers, echoes body. Handles redirect JSON payloads. |
| `buildResponse()` | `protected static function buildResponse(mixed $result, ?string $viewPath): Response` | If a `#[View]` attribute exists on the controller method, renders Mesh/Twig template. Otherwise wraps result in `Response`. |

---

### `Flames\Framework\Event`

**Final, internal.** Resolves and invokes application event classes from `{MicroservicePath}/Server/Event/{EventName}.php`.

| Method | Signature | How it works |
|--------|-----------|--------------|
| `dispatch()` | `public static function dispatch(mixed ...$args): RequestData\|bool\|string\|null` | Flexible dispatcher. Signatures: `dispatch('EventName', 'methodName', ...params)` or `dispatch(ContractInterface::class, 'EventName', 'methodName', ...params)`. Returns method result, event instance, or `null` if event file not found. |

**Resolution logic:**
- Syncs context when microservice changes (clears cached instances).
- Looks for file at `{MicroservicePath}/Server/Event/{EventName}.php`.
- Resolves class as `{MicroserviceNamespace}Server\Event\{EventName}`.
- Caches class name and singleton instance per event.

**Common events:**

| Event | Method | When called |
|-------|--------|-------------|
| `Initialize` | `onInitialize(): bool` | Before routing; return `false` to abort |
| `Route` | `onRoute(): void` | To register routes |
| `Route` | `onMatch(RequestData): bool` | After route match; return `false` to abort |
| `Output` | `onOutput(RequestData, string): ?string` | Before sending response body |

---

### `Flames\Framework\Header`

HTTP response header manager. Stores headers in a static array and sends them via PHP's `header()`.

| Method | Signature | How it works |
|--------|-----------|--------------|
| `set()` | `public static function set(string $key, mixed $value): void` | Stores a header key/value pair. |
| `get()` | `public static function get(string $key): mixed` | Returns stored header value or `null`. |
| `getAll()` | `public static function getAll(): Arr` | Returns all stored headers as `Arr`. |
| `clear()` | `public static function clear(): void` | Clears all stored headers. |
| `send()` | `public static function send(): void` | Sends HTTP status code (from `Code`/`code` key) and all headers. Silently returns in CLI. |
| `redirect()` | `public static function redirect(string $url, bool $sendNow = false): Arr\|string` | If `$sendNow`, sends `Location` header and exits. Otherwise returns `Arr(['flames.redirect' => $url])` for deferred redirect in dispatch. |

---

### `Flames\Framework\Connection`

Client IP address resolver.

| Method | Signature | How it works |
|--------|-----------|--------------|
| `getIp()` | `public static function getIp(bool $parseProxy = false): string\|null` | Returns client IP. When `$parseProxy === true`, checks `HTTP_CLIENT_IP` and `HTTP_X_FORWARDED_FOR` first. Falls back to `REMOTE_ADDR`. Caches result. |

---

### `Flames\Framework\Cache`

Resolves and creates the framework cache directory path.

| Method | Signature | How it works |
|--------|-----------|--------------|
| `getPath()` | `public static function getPath(): string` | Returns writable cache path. Reads `TMP_PATH` from env (relative values resolve under `ROOT_PATH`); falls back to `sys_get_temp_dir()/.tmp/` or `ROOT_PATH/.tmp/`. Ensures cache is **not** inside `public/`. Result is memoized via `Functions::once()`. |
| `forceCreateDirectory()` | `protected static function forceCreateDirectory(string $path)` | Creates directory with `0777` permissions. |

---

### `Flames\Framework\View`

Mesh/Twig template renderer for server-side views.

| Method | Signature | How it works |
|--------|-----------|--------------|
| `addView()` | `public function addView(string $path): void` | Sets the template path relative to `{MicroservicePath}/Server/View/`. Throws if file does not exist. |
| `render()` | `public function render(Arr\|array\|null $data = null): string` | Creates Twig `Environment` with filesystem loader and cache at `{CachePath}/flames/mesh/`. Renders template and passes result through `Surface\Kernel::inject()`. |

---

### `Flames\Framework\PersistentData`

**Internal.** Holds a global `WeakMap` for associating data with objects without preventing garbage collection.

| Property | Description |
|----------|-------------|
| `$weakMap` | Static `WeakMap` initialized at file load |

---

### `Flames\Framework\Shutdown`

**Internal.** Shutdown hook placeholder.

| Method | Signature | How it works |
|--------|-----------|--------------|
| `onShutdown()` | `public static function onShutdown()` | Empty placeholder for future shutdown logic. |

---

### `Flames\Framework\Controller\RequestData`

**Final.** Immutable value object representing an HTTP or CLI request.

**Properties:**

| Property | Type | Description |
|----------|------|-------------|
| `$method` | `string` | HTTP method or `'CLI'` |
| `$url` | `?string` | Request path |
| `$command` | `?string` | CLI command name |
| `$queries` | `array` | Query string parameters |
| `$uries` | `array` | Route URI parameters |
| `$multipart` | `array` | Multipart form data |
| `$urlEncoded` | `array` | URL-encoded form data |
| `$json` | `?array` | Parsed JSON body |
| `$request` | `array` | Merged request data (queries + body + route params) |
| `$headers` | `array` | Request headers |
| `$host` | `?string` | Server name |
| `$port` | `?int` | Server port |
| `$ip` | `?string` | Client IP |
| `$data` | `?array` | Extra custom data |

| Method | Signature | How it works |
|--------|-----------|--------------|
| `__construct()` | See properties above | Creates a request data instance with all fields. |
| `getBase()` | `public static function getBase(): self` | Returns a minimal GET `/` request with current server name, port, and IP. |

---

### `Flames\Framework\Controller\RequestMount`

**Final, internal.** Builds `RequestData` from the current HTTP request or CLI context.

| Method | Signature | How it works |
|--------|-----------|--------------|
| `mountRequestData()` | `public static function mountRequestData(RouteMatch $match, ?string $ip = null): RequestData` | Parses `REQUEST_URI`, `REQUEST_METHOD`, content type, query string, POST data, JSON body, and route parameters into a `RequestData` object. In CLI mode, returns a CLI request with route parameters as `$uries`. |
| `headers()` | `private static function headers(): array` | Collects headers via `getallheaders()` or parses `HTTP_*` server variables. |

---

### `Flames\Framework\Controller\Response`

**Final.** Normalized HTTP response wrapper.

**Properties (readonly):**

| Property | Type | Description |
|----------|------|-------------|
| `$data` | `mixed` | Original response data |
| `$output` | `string` | Encoded output string |
| `$statusCode` | `int` | HTTP status code |
| `$contentType` | `string` | Content-Type header value |

| Method | Signature | How it works |
|--------|-----------|--------------|
| `__construct()` | `(mixed $data = null, int $statusCode = 200, ?string $contentType = null)` | Auto-resolves content type: arrays/objects → `application/json`, strings → `text/html`. Encodes data to output string. |
| `from()` | `public static function from(mixed $result): self` | Returns existing `Response` or wraps value in new one. |
| `getViewData()` | `public function getViewData(): array\|Arr\|null` | Returns array/Arr data if present (for template rendering). |
| `getOutput()` | `public function getOutput(): string` | Returns encoded output. |
| `getStatusCode()` | `public function getStatusCode(): int` | Returns status code. |
| `getContentType()` | `public function getContentType(): string` | Returns content type. |
| `__get()` | `public function __get(string $key): mixed` | Magic access: `output`, `code`/`statuscode`, `contenttype`/`content-type`. |

---

### `Flames\Framework\Controller\View`

**PHP Attribute** (`TARGET_METHOD`). Marks a controller method for Mesh/Twig template rendering.

```php
#[View('users/list.html.twig')]
public function onRequest(RequestData $request): array
{
    return ['users' => $users];
}
```

| Method | Signature | How it works |
|--------|-----------|--------------|
| `__construct()` | `public function __construct(public readonly string $path)` | Stores the template path relative to `Server/View/`. |

---

### `Flames\Framework\Controller\Data`

**Internal.** Reflection cache for controller `#[View]` attributes.

| Method | Signature | How it works |
|--------|-----------|--------------|
| `getViewPath()` | `public static function getViewPath(string $class, string $method): ?string` | Returns the template path for a controller method, or `null` if no `#[View]` attribute. |
| `mountData()` | `public static function mountData(string $class): Arr` | Reads and caches controller reflection data in `{CachePath}/flames/framework-controller/`. |
| `__getReflection()` | `private static function __getReflection(string $class): Arr` | Scans all methods for `#[View]` attributes and stores method → path mapping. |

---

## Deprecated Classes (Legacy API)

> **Do not use these in new code.** They remain for backward compatibility with older Flames applications and client-side (WebAssembly) runtime.

---

### Core Deprecated

#### `Flames\AutoLoad` (final, internal)

Legacy autoloader. Replaced by `flamesphp/autoload` package.

| Property | Description |
|----------|-------------|
| `$event` | Whether `App/Server/Event/Load.php` exists |

| Method | How it works |
|--------|--------------|
| `run()` | Checks for event load file, registers `spl_autoload_register` callback |
| `onLoad($name)` | Loads classes by namespace: `Flames\Collection\*`, `Flames\Dumpper\*`, `Flames\*`, `App\*`, `Microservice\*` |

---

#### `Flames\Controller` (abstract)

Legacy base controller. Replaced by app-level controllers implementing `Flames\Interfaces\Controller`.

| Method | How it works |
|--------|--------------|
| `onRequest(RequestData): string\|array\|Arr\|Response` | Default handler; returns `success()` |
| `success($data, $code, $contentType)` | Builds response. If caller method has `#[View]`, renders template via legacy `Flames\View` |
| `error($data, $code, $contentType)` | Same as `success()` with error status code |
| `run(?string $method, Arr\|array $data)` | Instantiates controller and calls method directly (testing/CLI) |
| `__constructStatic()` | Loads method metadata via `Flames\Controller\Data` |

---

#### `Flames\Command`

CLI command runner.

| Method | How it works |
|--------|--------------|
| `run(string $command, bool $debug): bool` | Executes a shell command and returns success status |

---

#### `Flames\Cookie`

Browser cookie manager (client-side).

| Method | How it works |
|--------|--------------|
| `set($key, $value, ?int $expireTimestamp)` | Sets a cookie |
| `get($key): ?string` | Reads a cookie value |
| `remove($key)` | Deletes a cookie |
| `getAll(): Arr` | Returns all cookies |

---

#### `Flames\Email`

Email validation utilities.

| Method | How it works |
|--------|--------------|
| `isAddressValid($email): bool` | RFC-style format validation |
| `isAddressRewrite($email): bool` | Detects plus-addressing / rewrite patterns |
| `isAddressDisposable($email): bool` | Checks against disposable email domain list |
| `isAddressDnsValid($email): bool` | Validates MX/A DNS records |
| `getDisposableDomainsData($uri)` | Fetches/syncs disposable domain list |

---

#### `Flames\ErrorPage` (final)

HTTP error page dispatcher.

| Method | How it works |
|--------|--------------|
| `dispatch404()` | Renders 404 error page |
| `dispatch500()` | Renders 500 error page |
| `route404(): string\|bool` | Attempts to find a custom 404 route |

---

#### `Flames\File`

Filesystem utility.

| Method | How it works |
|--------|--------------|
| `exists($path): bool` | Checks file existence |
| `getMimeType($path): ?string` | Returns MIME type |
| `putContents($path, $contents): bool` | Writes file |
| `getContents($path): mixed` | Reads file |
| `delete($path): bool` | Deletes file |
| `move($old, $new): bool` | Moves/renames file |
| `copy($from, $to): bool` | Copies file |

---

#### `Flames\Header` (deprecated)

Legacy header manager. Identical API to `Flames\Framework\Header`. Use the Framework version instead.

---

#### `Flames\Image`

GD-based image manipulation.

| Method | How it works |
|--------|--------------|
| `__construct(?string $path)` | Loads image from path |
| `show($format)` | Outputs image to browser |
| `getString(): ?string` | Returns image as binary string |
| `crop($x, $y, $width, $height)` | Crops image region |
| `getText(): ?string` | OCR text extraction |
| `save($path, $format)` | Saves image to disk |
| `fromUrl($url): ?Image` | Loads image from URL |
| `fromString($string): ?Image` | Loads image from binary string |

---

#### `Flames\Js`

JavaScript bridge (client-side WebAssembly).

| Method | How it works |
|--------|--------------|
| `eval(string $code): mixed` | Evaluates JavaScript code in the browser |
| `getWindow(): Window\|Bridge\|null` | Returns the browser `window` object wrapper |

---

#### `Flames\Jwt`

JWT encode/decode wrapper.

| Method | How it works |
|--------|--------------|
| `__construct(?string $privateKeyPath, ?string $publicKeyPath)` | Initializes with RSA key paths |
| `setPrivateKeyByPath($path)` | Sets private key file |
| `setPublicKeyByPath($path)` | Sets public key file |
| `encode(Arr\|array $data)` | Creates signed JWT token |
| `decode(string $token)` | Validates and decodes JWT token |

---

#### `Flames\Php`

PHP eval wrapper (client-side).

| Method | How it works |
|--------|--------------|
| `eval(string $code): mixed` | Evaluates PHP code string |

---

#### `Flames\Server`

Server utility.

| Method | How it works |
|--------|--------------|
| `getUri($withQueryString): string` | Returns current request URI |
| `getUniqueId()` | Returns a unique server identifier |

---

#### `Flames\Wsl`

WSL (Windows Subsystem for Linux) detection.

| Method | How it works |
|--------|--------------|
| `isWsl(): bool` | Detects if running inside WSL |
| `isWsl2(): bool` | Detects WSL2 specifically |

---

### `Flames\Element` (deprecated)

DOM element wrapper for client-side PHP. Provides jQuery-like API over native browser elements.

| Method | How it works |
|--------|--------------|
| `query($selector): ?Element` | CSS selector query (single) |
| `queryAll($selector): ?Arr` | CSS selector query (all) |
| `fromNative($element): Element` | Wraps native DOM element |
| `fromUid($uid): Element` | Finds element by Flames UID |
| `getBody(): Element` | Returns document body |
| `hasClass/addClass/removeClass/toogleClass($class)` | CSS class manipulation |
| `hasAttribute/getAttribute/setAttribute/removeAttribute($attr)` | Attribute manipulation |
| `getStyle/setStyle($style, $value)` | Inline style manipulation |
| `getHtml/setHtml/appendHtml/htmlInsertEnd($html)` | HTML content manipulation |
| `getValue/setValue($value)` | Form input value |
| `getChecked/setChecked($bool)` | Checkbox state |
| `getParent(): ?Element` | Parent element |
| `getShadow(): ?Shadow` | Shadow DOM root |
| `destroy()` | Removes element from DOM |
| `__get/__set($key, $value)` | Property access (id, className, innerHTML, etc.) |

---

### `Flames\Element\Event` (deprecated)

Event listener registration on DOM elements.

| Method | How it works |
|--------|--------------|
| `click(\Closure $delegate)` | Registers click handler |
| `change(\Closure $delegate)` | Registers change handler |
| `input(\Closure $delegate)` | Registers input handler |
| `keyDown(\Closure $delegate)` | Registers keydown handler |
| `keyUp(\Closure $delegate)` | Registers keyup handler |
| `focus(\Closure $delegate)` | Registers focus handler |

---

### `Flames\Element\Shadow` (deprecated)

Shadow DOM query helper.

| Method | How it works |
|--------|--------------|
| `subquery($query): ?Element` | Queries inside shadow root |

---

### Client-Side Classes (`Flames\Client\*`)

#### `Flames\Client\Browser`

| Method | How it works |
|--------|--------------|
| `getName($includeVersion): string\|Arr` | Returns browser name (and optionally version) via UserAgentParser |
| `getVersion()` | Returns browser version string |

#### `Flames\Client\Platform`

OS/platform detection with extensive constants (Windows, Linux, macOS, Android, iOS, consoles, etc.).

| Method | How it works |
|--------|--------------|
| `getName(): string` | Platform name from user agent |
| `isWindows/isLinux/isUnix/isIos/isDarwin/isMacintosh/isAndroid/isBsd/isMobile/isConsole/isPlaystation/isXbox/isNintendo(): bool` | Platform type checks |
| `getArch(): ?string` | CPU architecture (native build only) |
| `getVersion(): ?string` | OS version (native build only) |

#### `Flames\Client\UserAgentParser`

| Method | How it works |
|--------|--------------|
| `getRaw(): ?string` | Raw user agent string |
| `parse($u_agent): array` | Parses UA into browser, version, platform, device info |
| `__invoke($u_agent): array` | Callable alias for `parse()` |

#### `Flames\Client\Tag`

PHP Attribute for custom Web Components.

| Method | How it works |
|--------|--------------|
| `__construct($uid, $path)` | Binds to shadow DOM element |
| `onRender()` | Override to render custom tag content |
| `getShadow(): ?Shadow` | Returns shadow DOM wrapper |

#### `Flames\Client\Browser\DevTools`

Mobile DevTools bridge (works without Chrome Remote DevTools).

#### `Flames\Client\Clipboard` / `Flames\Client\Clipboard\Event`

| Method | How it works |
|--------|--------------|
| `Event::paste(\Closure $delegate)` | Registers clipboard paste handler |

#### `Flames\Client\Keyboard` / `Flames\Client\Keyboard\Event`

Keyboard event registration for client-side.

#### `Flames\Client\Native`

Native app (ElectronJS) bridge utilities.

#### `Flames\Client\Os`

Client-side OS detection (lighter than Platform).

#### `Flames\Client\Shell`

Client-side shell command bridge.

#### `Flames\Client\View`

Client-side view/template helper.

#### `Flames\Client\Event\Click/Change/Input`

Client-side DOM event data objects (mirror server-side Element events).

---

### Server-Side Deprecated (`Flames\Server\*`)

#### `Flames\Server\Os`

Server OS detection.

| Method | How it works |
|--------|--------------|
| `getName(): string` | OS name (Windows, Linux, Darwin, etc.) |
| `isUnix/isLinux/isWindows/isBsd/isSolaris/isDarwin/isMacintosh(): bool` | OS type checks |

#### `Flames\Server\Os\Mouse`

Windows mouse automation (Platform Engine).

| Method | How it works |
|--------|--------------|
| `getPosition(): Arr` | Returns `{x, y}` mouse coordinates via PowerShell |
| `setPosition($x, $y, $leftClick)` | Moves mouse cursor |
| `click($type)` | Performs mouse click (`left`, `right`, etc.) |

#### `Flames\Server\Os\Screen\Image`

| Method | How it works |
|--------|--------------|
| `take()` | Captures screenshot via batch script |

#### `Flames\Server\Process`

Process management.

| Method | How it works |
|--------|--------------|
| `__construct(?string $command)` | Starts a new process |
| `getPid(): ?int` | Returns process ID |
| `destroy()` | Kills the process |
| `getCurrent()` | Returns current process instance |
| `isRunning(int $pid): bool` | Checks if PID is alive |

#### `Flames\Server\Process\Wsl`

WSL process wrapper. Extends `Process`, runs commands inside WSL.

#### `Flames\Server\Shell`

Shell command executor.

| Method | How it works |
|--------|--------------|
| `__construct(?string $command)` | Executes shell command |
| `getCode(): ?int` | Returns exit code |
| `getOutput(): ?string` | Returns stdout output |

#### `Flames\Server\Shell\Code`

Shell exit code constants/helper.

---

### Connection Deprecated (`Flames\Connection\*`)

#### `Flames\Connection\Client`

Legacy IP resolver. Same as `Flames\Framework\Connection::getIp()`.

#### `Flames\Connection\HttpClient`

Detects HTTP testing tools by User-Agent.

| Method | How it works |
|--------|--------------|
| `isHttpClient(): bool` | True if any known HTTP client tool |
| `isPostman/isSwagger/isInsomnia/isJetBrains/isThunderClient/isKatalon/isHttpPie/isPetApi/isHoppscotch/isJmeter/isSoapUi/isTestSigma/isReadyApi/isAssertible/isPaw/isKarateLabs/isTricentisTosca/isLoadNinja/isApiGee(): bool` | Individual tool detection |

---

### Crypto Deprecated (`Flames\Crypto\*`)

#### `Flames\Crypto\Hash`

| Method | How it works |
|--------|--------------|
| `getRandom(int $length): string` | Generates cryptographically random string |

#### `Flames\Crypto\Password`

| Method | How it works |
|--------|--------------|
| `toHash(string $password, ?string $key): string` | Creates password hash |
| `isValidHash(string $hash, string $password, ?string $key): bool` | Validates password against hash |

#### `Flames\Crypto\Hashid`

Hashids encoder/decoder (URL-safe ID obfuscation).

| Method | How it works |
|--------|--------------|
| `encode(...$numbers): string` | Encodes integers to hash string |
| `decode(string $hash)` | Decodes hash to integers |
| `encodeHex(string $str): string` | Encodes hex string |
| `decodeHex(string $hash): string` | Decodes to hex string |

#### `Flames\Crypto\Hashid\BcMath` / `Flames\Crypto\Hashid\Gmp`

Math backends for Hashid (bcmath and gmp extensions).

---

### Cache Deprecated (`Flames\Cache\*`)

#### `Flames\Cache\Memory`

In-memory cache store.

#### `Flames\Cache\Memory\Client`

Client-side in-memory cache accessor.

---

### JWT Deprecated (`Flames\Jwt\*`)

| Class | Purpose |
|-------|---------|
| `JWT` | Core JWT encode/decode/sign/verify |
| `Key` | Cryptographic key wrapper |
| `JWK` | JSON Web Key parsing |
| `CachedKeySet` | JWKS cache with `ArrayAccess` |
| `ExpiredException` | Token expired |
| `BeforeValidException` | Token not yet valid |
| `SignatureInvalidException` | Invalid signature |
| `JWTExceptionWithPayloadInterface` | Exception with decoded payload |

---

### Event Classes Deprecated (`Flames\Event\*`)

Abstract event base classes for client-side lifecycle hooks:

| Class | Method | When |
|-------|--------|------|
| `Initialize` | `onInitialize(): bool` | App initialization |
| `Load` | `onLoad(string $name): bool` | Module load |
| `Ready` | `onReady(): void` | DOM ready |
| `Native` | `onNative(): void` | Native app bridge ready |
| `Page` | `onPreLoad($uri)`, `onLoad($html)`, `onPostLoad()` | Page lifecycle |
| `Output` | `onOutput(RequestData, ?string): ?string` | Response output hook |
| `Native\Shell` | Constructor + `__get` | Native shell command result |

**Element event data objects** (passed to event handlers):

| Class | Properties / Methods |
|-------|---------------------|
| `Element\Click` | `$target` (Element) |
| `Element\Change` | `$target` (Element) |
| `Element\Input` | `$target` (Element) |
| `Element\Focus` | `$target` (Element), `$focus` (bool) |
| `Element\KeyDown` | `$target`, `isKeyDown()`, `isKeyCodeDown()`, `isCombination()` |
| `Element\KeyUp` | `$target`, `isKeyUp()`, `isKeyCodeUp()` |
| `Clipboard\Paste` | Clipboard paste event data |

---

### Kernel Deprecated (`Flames\Kernel\*`)

#### `Flames\Kernel\Config`

| Method | How it works |
|--------|--------------|
| `get(): mixed` | Returns loaded config |
| `set(mixed $data): void` | Sets config data |

#### `Flames\Kernel\Forge`

Docker/CLI forge tool bootstrap.

| Method | How it works |
|--------|--------------|
| `boot(string $projectRoot, string $kernelFile): void` | Boots forge CLI in Docker or local environment |

#### `Flames\Kernel\Sync`

File-based data sync cache.

| Method | How it works |
|--------|--------------|
| `getData(string $key)` | Reads synced data by key from cache directory |

#### `Flames\Kernel\Tools\WinIco`

Windows ICO file generator/converter.

#### `Flames\Kernel\Client` (final)

Client kernel data injection bridge.

| Method | How it works |
|--------|--------------|
| `__getData()` | Returns injected client data |
| `__injectData($data)` | Injects data from JS to PHP |
| `__injector()` | Returns injector callback |
| `isNativeBuild(): bool` | Whether running as native Electron app |
| `__setNativeBuild(bool): bool` | Sets native build flag |

#### `Flames\Kernel\Client\Dispatch` (final)

Client-side request dispatcher (WebAssembly runtime). Mirrors server dispatch but for browser context.

| Method | How it works |
|--------|--------------|
| `run()` | Starts client dispatch |
| `runAsync($firstLoad)` | Async client boot |
| `getInstance(string $class): mixed` | Gets singleton controller instance |
| `injectUri(string $uri)` | Injects URI for client routing |

#### `Flames\Kernel\Client\Dispatch\Native`

Native app build dispatch.

#### `Flames\Kernel\Client\Dispatch\Tag`

Custom tag dispatch handler.

#### `Flames\Kernel\Client\Error` (final)

| Method | How it works |
|--------|--------------|
| `handler(\Exception\|\Error $e): void` | Client-side error handler |

#### `Flames\Kernel\Client\Virtual` (final, internal)

Virtual class loader for client-side precompiled PHP.

| Method | How it works |
|--------|--------------|
| `load(string $class): bool` | Evaluates base64-encoded class buffer |
| `getTagClass(string $tag)` | Returns tag class for custom element |
| `getViews()` | Returns registered client views |

#### `Flames\Kernel\Client\Service\Clipboard`

Client clipboard service bridge.

#### `Flames\Kernel\Client\Service\Keyboard`

Client keyboard service bridge.

#### `Flames\Surface\Surface\Js\Bridge`

Extends `Flames\Js\Window` — Surface JS runtime wrapper for the browser `window` object.

---

## Conventions for AI Agents

### Creating a Server Controller

```php
namespace App\Server\Controller;

use Flames\Framework\Controller\RequestData;
use Flames\Framework\Controller\Response;
use Flames\Framework\Controller\View;

class Home
{
    #[View('home/index.html.twig')]
    public function onRequest(RequestData $request): array
    {
        return ['title' => 'Welcome'];
    }
}
```

Place controllers in `App/Server/Controller/` (or `{Microservice}/Server/Controller/`).

### Creating an Event Handler

```php
namespace App\Server\Event;

use Flames\Interfaces\Event\Route as RouteContract;

class Route implements RouteContract
{
    public function onRoute(): void
    {
        \Flames\Router::get('/', 'Home', 'onRequest');
    }

    public function onMatch(\Flames\Framework\Controller\RequestData $request): bool
    {
        return true; // return false to abort
    }
}
```

Place in `App/Server/Event/Route.php`.

### Creating a Queue Worker

```php
namespace App\Queue;

use Flames\Queue;
use Flames\Orm\Attribute\Database;
use Flames\Orm\Attribute\Table;
use Flames\Queue\Delay;
use Flames\Queue\TimeLimit;

#[Database(name: 'default')]
#[Table(name: 'jobs_email')]
#[Delay(delay: 0.2)]
#[TimeLimit(timelimit: 300)]
class EmailQueue extends Queue
{
    public static function onMessage(int $id, mixed $data): void
    {
        // Process $data, then optionally:
        // self::removeById($id);
    }
}
```

### Response Patterns

| Return type | Result |
|-------------|--------|
| `string` | Sent as HTML (`text/html`) |
| `array` / `Arr` | JSON encoded (`application/json`) |
| `Response` | Uses explicit status code and content type |
| `Header::redirect($url)` | Deferred redirect (handled by dispatch) |
| `array` + `#[View]` attribute | Rendered through Mesh/Twig template |

### What NOT to Do

- Do not import from `Flames\(deprecated)\` in new server code.
- Do not call `Kernel` methods directly from application code — use `index.php` or CLI entry points.
- Do not modify `@internal` classes unless fixing the framework itself.
- Queue requires PDO — ensure your ORM database driver uses `Flames\Orm\Database\RawConnection\Pdo`.
- Cache path must be writable and outside `public/`.

### External Packages (not in this repo)

This framework package depends on sibling packages under `vendor/flamesphp/`:

| Package | Purpose |
|---------|---------|
| `flamesphp/autoload` | PSR-4 autoloading |
| `flamesphp/collection` | `Flames\Collection\Arr` and utilities |
| `flamesphp/router` | `Flames\Router` routing |
| `flamesphp/env` | `Flames\Env\Env` environment |
| `flamesphp/mesh` | Twig templating (`Flames\Mesh\Environment`) |
| `flamesphp/orm` | Database ORM |
| `flamesphp/errors` | Error handler (`Flames\Errors\Run`) |
| `flamesphp/surface` | Template injection (`Flames\Surface\Kernel`) |

When generating code, assume these packages are available via Composer.

---

*Generated for FlamesPHP Framework v26. For framework overview and installation, see [README.md](README.md).*
