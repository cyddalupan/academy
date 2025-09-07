CREATE TABLE custom_users_course (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    course_id INTEGER,
    remaining_seconds INTEGER,
    date_created DATETIME,
    average_score REAL,
    summary TEXT
);

CREATE TABLE quiz_new (
    q_id INTEGER PRIMARY KEY AUTOINCREMENT,
    q_question TEXT,
    q_answer TEXT,
    q_level INTEGER,
    q_timer INTEGER,
    q_course_id INTEGER
);

CREATE TABLE diag_ans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    batch_id INTEGER,
    question_id INTEGER,
    answer TEXT,
    score INTEGER,
    feedback TEXT,
    date_created DATETIME
);

CREATE TABLE course (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT
);

INSERT INTO custom_users_course (user_id, course_id, remaining_seconds, date_created, average_score, summary) VALUES (1, 1, 3600, '2025-09-07 12:00:00', 85.5, 'Good job!');
INSERT INTO quiz_new (q_question, q_answer, q_level, q_timer, q_course_id) VALUES ('What is the capital of France?', 'Paris', 1, 60, 1);
INSERT INTO diag_ans (user_id, batch_id, question_id, answer, score, feedback, date_created) VALUES (1, 1, 1, 'Paris', 100, 'Correct!', '2025-09-07 12:01:00');
INSERT INTO course (id, title) VALUES (1, 'Geography');