<?php

class Controller
{
    function __construct()
    {
        require "config.php";
        require "model.php";
        require "view.php";

        $this->model = new Model();
        $this->view = new View();
    }
    
    public function get_login()
    {
        $this->view->display('login');
    }

    public function post_login()
    {
        $username = hash('whirlpool', $_POST['username']);
        $password = hash('whirlpool', $_POST['password']);

        if($username === USERNAME && $password === PASSWORD)
        {
            // Redirect to the dashboard on success
            $_SESSION['logged_in'] = true;
            $this->view->display('redirect', array
            (
                'action' => 'dashboard',
                'message' => 'Login successful!<br />Redirecting...',
                'time' => 2
            ));
        }
        else
        {
            // Redirect back to the login page on failure
            $_SESSION['logged_in'] = false;
            $this->view->display('redirect', array
            (
                'action' => 'login',
                'time' => 0
            ));
        }
    }
    
    public function action($action)
    {
        // Use the post handler if post data exists
        if($_POST) {
            $action = "post_$action";            
        }

        // Else, fallback to the get handler
        else {
            $action = "get_$action";            
        }

        // Call the requested action if it exists
        if(method_exists($this, $action))
        {
            $this->$action();
            return true;
        }

        // Otherwise return false
        return false;
    }
}