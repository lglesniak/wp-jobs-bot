# WP Jobs Bot

A small PHP bot that checks job board RSS feeds once a day and sends new WordPress developer vacancies to Telegram.

It runs on GitHub Actions on a schedule, so no server is needed.

## How it works

1. Fetches RSS feeds from Djinni, DOU and jobs.wordpress.net (configured in `config.php`).
2. Parses each feed into vacancies. Tracking parameters are stripped from URLs, so the same vacancy from two feeds is detected as a duplicate.
3. Filters vacancies:
   - the title or description must mention WordPress, WooCommerce or Gutenberg (some feeds ignore search parameters and return every vacancy);
   - titles with excluded keywords (designer, junior, pure SEO roles, etc.) are skipped.
4. Skips vacancies that were already sent (`data/seen.json`).
5. Sends each new vacancy as a Telegram message and only then marks it as sent, so a failed run retries next time.
6. GitHub Actions commits the updated `data/seen.json` back to the repository.

A broken feed is logged and skipped; the run fails only if every feed is broken.

## Project structure

```
bin/run.php               Entry point (also supports --dry-run)
config.php                Feeds, filters and limits
src/FeedParser.php        RSS → Vacancy objects
src/VacancyFilter.php     Include / exclude keyword rules
src/SeenStore.php         Remembers sent vacancy IDs in JSON
src/JobChecker.php        Combines feeds, filter and store; isolates feed errors
src/TelegramNotifier.php  Sends messages via the Telegram Bot API
src/CurlHttpClient.php    HTTP client (behind the HttpClient interface for tests)
tests/                    PHPUnit tests with a fake HTTP client
.github/workflows/        Daily schedule and CI
```

## Setup

### 1. Create a Telegram bot

1. In Telegram, open [@BotFather](https://t.me/BotFather), send `/newbot` and follow the steps. Copy the bot token.
2. Open your new bot and send it any message (bots cannot write to you first).
3. Open `https://api.telegram.org/bot<TOKEN>/getUpdates` in a browser and copy `message.chat.id` — this is your chat ID.

Keep the token private: anyone with it can control the bot.

### 2. Add GitHub secrets

In the repository: **Settings → Secrets and variables → Actions → New repository secret**:

- `TELEGRAM_BOT_TOKEN` — the bot token
- `TELEGRAM_CHAT_ID` — your chat ID

### 3. Run it

**Actions → Daily vacancies → Run workflow** to test immediately. After that it runs every day at about 05:17 UTC (GitHub may delay scheduled runs).

The first run sends everything currently in the feeds (up to `max_per_run`); later runs send only new vacancies.

## Local development

Requires PHP 8.1+ with curl, mbstring and SimpleXML.

```bash
composer install
composer test        # run PHPUnit
composer check       # dry run: print new vacancies, send and save nothing
```

To send messages locally, set `TELEGRAM_BOT_TOKEN` and `TELEGRAM_CHAT_ID` environment variables and run `php bin/run.php`.

## Customizing

Edit `config.php` to add feeds (any RSS 2.0 feed works) or change the keyword filters.
