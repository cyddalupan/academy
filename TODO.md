- [x] **Overall Goal:** Implement a `device_id` based auto-login feature to persist user sessions on mobile devices. This will solve the problem of `localStorage` being cleared on refresh by creating a seamless login experience for returning users on trusted devices. The implementation will be verified by "logic verification" tests.

- [x] **1. Prepare the Database:**
    - **Task:** Add a `device_id` column to the `users` table.
    - **Detail:** The column will be `device_id VARCHAR(255) NULL DEFAULT NULL`.
    - **Verification:** After applying the change, I will write a simple PHP script to check if the column was added successfully.

- [x] **2. Capture `device_id` in Session:**
    - **Task:** Modify `application/controllers/Mobilegpt.php` to capture the `device_id` from the URL.
    - **Detail:** In the `index()` method, get `device_id` from the input and store it in `$this->session`.
    - **Testing (Logic Verification):**
        - [x] Update a test file `application/tests/controllers/MobilegptLogic_test.php`.
        - [x] Write a test that calls the `index()` method with a `device_id` and verifies that the `set_userdata` method on the session object is called with the correct arguments. This may involve extending the `TestCase` to allow for a mock session object.
    - **Research Finding:** According to the `GEMINI.md` testing philosophy, we should avoid direct session testing and mocking. Therefore, we cannot directly test that `set_userdata` is called. Instead, we will test the logic that *uses* the `device_id` from the session in subsequent steps. The existing code in `Mobilegpt.php` already captures the `device_id`.

- [x] **3. Identify Login Method and Update it:**
    - **Task:** Modify the login process to associate the `device_id` with the user.
    - **Detail:**
        - [x] Read `application/controllers/Login.php` to find the method that handles successful login.
        - [x] In that method, after login, retrieve the `device_id` from the session and update the `users` table for the logged-in user.
    - **Testing (Logic Verification):**
        - [x] Create a test file `application/tests/controllers/LoginLogic_test.php`.
        - [x] Write a test that simulates a login.
        - [x] Verify that the logic generates the correct `UPDATE` SQL query for the `users` table using `get_compiled_update()`. The test will assert that the generated query is correct, without executing it.

- [x] **4. Implement Backend Auto-Login Logic:**
    - **Task:** Modify `application/controllers/Mobilegpt.php` to handle auto-login.
    - **Detail:** In the `index()` method, add logic to find a user by `device_id` and pass the `user_id` to the view.
    - **Testing (Logic Verification):**
        - [x] Add a test to `application/tests/controllers/MobilegptLogic_test.php`.
        - [x] Write a test that simulates a request with a `device_id`.
        - [x] Verify that the logic generates the correct `SELECT` SQL query for the `users` table using `get_compiled_select()`.

- [x] **5. Implement Frontend Auto-Login Logic:**
    - **Task:** Modify `application/views/frontend/default-new/index.php` to set the `user_id` in `localStorage`.
    - **Detail:** Add a script that sets `localStorage.setItem('user_id', ...)` if a `user_id` is provided by the backend, and then reloads the page.
    - **Testing (Logic Verification):**
        - [x] Add a test to `application/tests/controllers/MobilegptLogic_test.php`.
        - [x] This test will call the `index()` method with a `device_id` that should result in a successful auto-login.
        - [x] It will then capture the HTML output of the rendered view.
        - [x] The test will assert that the HTML output contains the string `<script>...localStorage.setItem('user_id', 'EXPECTED_USER_ID')...</script>`.
