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
        if($_SESSION['logged_in'])
        {
            $this->view->display('redirect', array
            (
                'action' => 'dashboard',
                'time' => 0
            ));
        }
        else
        {
            $this->view->display('login');
        }
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
                'message' => 'Login successful!<br>Redirecting...',
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

    public function get_logout()
    {
        session_destroy();
        $this->view->display('redirect', array
        (
            'action' => 'login',
            'message' => 'Logout successful!<br>Redirecting...',
            'time' => 2
        ));
    }

    public function get_dashboard()
    {
        if($_SESSION['logged_in'])
        {
            $this->view->display('dashboard', array
            (
                'dev_upload' => filemtime('uploads/development_export.sql'),
                'prod_upload' => filemtime('uploads/production_export.sql')
            ));
        }
        else
            $this->view->display('login');
    }

    public function get_upload()
    {
        if($_SESSION['logged_in'])
            $this->view->display('upload');
        else
            $this->view->display('login');
    }

    public function post_upload()
    {
        if($_SESSION['logged_in'])
        {
            $error = false;
            
            foreach($_FILES as $field => $file)
            {
                if($file['error'])
                {
                    if($file['error'] != UPLOAD_ERR_NO_FILE)
                        $error = true;
                }
                else
                {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    if($mime_type != "text/plain")
                        $error = true;
                    {
                        if($field == "file_1")
                            $name = "development_export";
                        else
                            $name = "production_export";

                        move_uploaded_file($file['tmp_name'], "uploads/$name.sql");
                    }
                }
            }

            if($error)
            {
                $redirect = array
                (
                    'action' => 'upload',
                    'message' => 'There was an error uploading your file.<br>Please ensure it is a plaintext .sql dump and try again!',
                    'time' => 4
                );
            }
            else
            {
                $redirect = array
                (
                    'action' => 'dashboard',
                    'message' => 'Upload completed!<br>Please wait while your uploaded data is imported...',
                    'time' => 3
                );
            }

            $this->view->display('redirect', $redirect);
        }
    }

    public function get_import()
    {
        if($_SESSION['logged_in'])
        {
            echo "Please wait, your data is being imported...";
            $this->model->import();
            
            $this->view->display('redirect', array
            (
                'action' => 'dashboard',
                'time' => 0
            ));
        }
    }

    // General handler to send actions to their
    public function action($action)
    {
        // Use the post handler if post/file data exists
        if($_POST || $_FILES) {
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
