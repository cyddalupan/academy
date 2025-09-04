# Database Schema

This document outlines the database schema for the application.

## `diag_ans`
- `id`: `int` (Primary Key, Auto Increment)
- `user_id`: `int` (Not Null)
- `batch_id`: `int` (Not Null, Default: 0)
- `question_id`: `int` (Not Null)
- `answer`: `text` (Not Null)
- `score`: `int`
- `feedback`: `text`
- `date_created`: `timestamp` (Default: CURRENT_TIMESTAMP)

## `gpt_payments`
- `id`: `int unsigned` (Primary Key, Auto Increment)
- `user_id`: `int unsigned` (Not Null)
- `receipt_image`: `varchar(255)` (Not Null)
- `status`: `enum('pending','approved','rejected')` (Default: 'pending')
- `notes`: `text`
- `created_at`: `timestamp` (Default: CURRENT_TIMESTAMP)
- `updated_at`: `timestamp` (Default: CURRENT_TIMESTAMP on update)
- `remarks`: `text`

## `gpt_premium`
- `id`: `int unsigned` (Primary Key, Auto Increment)
- `user_id`: `int unsigned` (Not Null, Unique)
- `expiration_date`: `date` (Not Null)
- `created_at`: `timestamp` (Default: CURRENT_TIMESTAMP)
- `updated_at`: `timestamp` (Default: CURRENT_TIMESTAMP on update)
- `remarks`: `varchar(225)` (Not Null)

## `offline_payment`
- `id`: `int unsigned` (Primary Key, Auto Increment)
- `user_id`: `int`
- `amount`: `varchar(255)`
- `course_id`: `varchar(255)`
- `item_id`: `varchar(255)`
- `item_type`: `varchar(255)`
- `item_info`: `longtext`
- `document_image`: `varchar(255)`
- `timestamp`: `varchar(255)`
- `status`: `int` (Not Null, Default: 0)
- `course_referee`: `varchar(255)`
- `referred_item_id`: `int`

## `question`
- `id`: `int unsigned` (Primary Key, Auto Increment)
- `quiz_id`: `int`
- `title`: `longtext`
- `type`: `varchar(255)`
- `number_of_options`: `int`
- `options`: `longtext`
- `correct_answers`: `longtext`
- `order`: `int` (Not Null, Default: 0)

## `quiz_new`
- `q_id`: `int` (Primary Key, Auto Increment)
- `q_course_id`: `int` (Not Null)
- `q_subject_id`: `int` (Not Null)
- `q_question`: `text` (Not Null)
- `q_answer`: `text` (Not Null)
- `q_level`: `varchar(20)` (Not Null)
- `q_timer`: `int` (Not Null)
- `q_ceeated`: `datetime` (Default: CURRENT_TIMESTAMP)

## `users`
- `id`: `int unsigned` (Primary Key, Auto Increment)
- `first_name`: `varchar(255)`
- `last_name`: `varchar(255)`
- `email`: `varchar(50)`
- `phone`: `varchar(255)`
- `address`: `varchar(255)`
- `password`: `varchar(255)`
- `skills`: `longtext` (Not Null)
- `social_links`: `longtext`
- `biography`: `longtext`
- `role_id`: `int`
- `date_added`: `int`
- `last_modified`: `int`
- `wishlist`: `longtext`
- `title`: `longtext`
- `payment_keys`: `longtext` (Not Null)
- `verification_code`: `longtext`
- `status`: `int`
- `is_instructor`: `int` (Default: 0)
- `image`: `varchar(255)`
- `temp`: `longtext`
- `sessions`: `longtext` (Not Null)
- `referalcode`: `varchar(100)` (Not Null)
