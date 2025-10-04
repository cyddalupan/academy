<?php
class Mobilegpt_test extends TestCase
{
    public function test_index()
    {
        $output = $this->request('GET', 'frontend/default-new/mobilegpt');
        $this->assertStringContainsString('<chatbot-widget></chatbot-widget>', $output);
        $this->assertStringContainsString('<style type="text/css">', $output);
        $this->assertStringContainsString('header, section.footer {', $output);
    }
}
