<?php

class Login_test extends TestCase
{
    /**
     * This test checks the default behavior of the main template.
     * It ensures that with no special parameters, the header and footer are rendered.
     */
    public function test_main_template_renders_header_and_footer_by_default()
    {
        // We need to pass 'page_name' and 'page_title' so the template can render without errors
        $output = $this->request('GET', 'frontend/default-new/index', ['page_name' => 'login', 'page_title' => 'Login']);

        $this->assertStringContainsString('<!-- Topbar Area Start -->', $output, "Header should be present by default.");
        $this->assertStringContainsString('<footer class="footer-area">', $output, "Footer should be present by default.");
    }

    /**
     * This test checks the new iframe logic in the main template.
     * It passes the 'is_iframe' => true flag and asserts that the header and footer are NOT rendered.
     * This directly tests the "if(!isset($is_iframe))" conditions we added.
     */
    public function test_main_template_hides_header_and_footer_for_iframe()
    {
        // Pass 'is_iframe' => true and 'page_title' to simulate the controller's action.
        $output = $this->request('GET', 'frontend/default-new/index', ['page_name' => 'login', 'page_title' => 'Login', 'is_iframe' => true]);

        $this->assertStringNotContainsString('<!-- Topbar Area Start -->', $output, "Header should be hidden for iframe view.");
        $this->assertStringNotContainsString('<footer class="footer-area">', $output, "Footer should be hidden for iframe view.");
    }
}
