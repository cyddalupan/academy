<?php

class MobilegptLogic_test extends TestCase
{
    public function test_relogin_script_is_present_when_relogin_script_is_true()
    {
        $output = $this->request('GET', 'frontend/default-new/mobilegpt', ['relogin_script' => true]);

        $this->assertStringContainsString('<script>', $output);
        $this->assertStringContainsString('localStorage.getItem(\'user_id\')', $output);
    }

    public function test_relogin_script_is_not_present_when_relogin_script_is_false()
    {
        $output = $this->request('GET', 'frontend/default-new/mobilegpt', ['relogin_script' => false]);

        $this->assertStringNotContainsString('<script>', $output);
    }

    public function test_auto_login_script_is_present_when_user_id_is_provided()
    {
        $output = $this->request('GET', 'frontend/default-new/index', ['page_name' => 'mobilegpt', 'user_id' => '123', 'is_test' => true, 'page_title' => '']);

        $this->assertStringContainsString('<script type="text/javascript">', $output);
        $this->assertStringContainsString('localStorage.setItem(\'user_id\', \'123\');', $output);
        $this->assertStringContainsString('window.location.reload();', $output);
    }
}
