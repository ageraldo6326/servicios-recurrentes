<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-5-mini'),
        'proxy' => env('OPENAI_PROXY'),
        'connect_timeout' => (int) env('OPENAI_CONNECT_TIMEOUT', 10),
        'timeout' => (int) env('OPENAI_TIMEOUT', 60),
        'cache_minutes' => (int) env('OPENAI_CACHE_MINUTES', 10),
    ],

    'ai_analysis' => [
        'enabled' => (bool) env('AI_ANALYSIS_ENABLED', true),
        'max_content_characters' => (int) env('AI_ANALYSIS_MAX_CONTENT_CHARACTERS', 20000),
        'max_question_characters' => (int) env('AI_ANALYSIS_MAX_QUESTION_CHARACTERS', 2000),
        'requests_per_minute' => (int) env('AI_ANALYSIS_REQUESTS_PER_MINUTE', 6),
        'requests_per_day' => (int) env('AI_ANALYSIS_REQUESTS_PER_DAY', 40),
        'input_cost_per_million_tokens' => env('AI_ANALYSIS_INPUT_COST_PER_MILLION_TOKENS'),
        'output_cost_per_million_tokens' => env('AI_ANALYSIS_OUTPUT_COST_PER_MILLION_TOKENS'),
    ],

];
