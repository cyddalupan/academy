<?php
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @var CI_Controller
     */
    protected $CI;

    public function setUp(): void
    {
        parent::setUp();

        // Manually load the CodeIgniter instance
        $this->CI = &get_instance();
    }

    /**
     * Make a request to the application
     *
     * @param string $method
     * @param string $uri
     * @param array  $params
     * @return string
     */
    public function request(string $method, string $uri, array $params = []): string
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI'] = $uri;

        // Set the parameters
        if ($method === 'GET') {
            $_GET = $params;
        } else {
            $_POST = $params;
        }

        // Capture the output
        ob_start();
        try {
            $view = $this->CI->load->view;
            return $view($uri, $params);
        } finally {
            ob_end_clean();
        }
    }
}
