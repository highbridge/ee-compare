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
        return true;
    }

    public function post_login()
    {

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
            return $this->$action();
        }

        // Otherwise return false
        return false;
    }
}