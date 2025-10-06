<?php

class LoginLogic_test extends TestCase
{
    public function test_device_id_is_updated_on_login()
    {
        // Set a device_id in the session
        $this->CI->session->set_userdata('device_id', 'test_device_id');

        // Mock the database
        $this->CI->db = $this->getMockBuilder('CI_DB_query_builder')
            ->disableOriginalConstructor()
            ->getMock();

        // Mock the user model
        $this->CI->user_model = $this->getMockBuilder('User_model')
            ->disableOriginalConstructor()
            -            ->getMock();

        // Expect the update method to be called with the correct parameters
        $this->CI->db->expects($this->once())
            ->method('where')
            ->with('id', 1);

        $this->CI->db->expects($this->once())
            ->method('update')
            ->with('users', ['device_id' => 'test_device_id']);

        // Create a mock query result
        $query = $this->getMockBuilder('CI_DB_result')
            ->disableOriginalConstructor()
            ->getMock();
        $query->method('num_rows')->willReturn(1);
        $query->method('row')->willReturn((object)['id' => 1]);

        $this->CI->db->method('get_where')->willReturn($query);

        // Call the validate_login method
        $login_controller = new Login();
        $login_controller->validate_login();
    }
}
