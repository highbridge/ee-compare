<?php

class View
{
    // Private function to generate global variables available in all views
    private function _global_vars()
    {
        return array
        (
            'site_url' => 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF']
        );
    }
    
    // Private function to require views without leaking any variables besides those provided
    private function _require()
    {
        // Extract converts all of the values from our array into variables
        extract(func_get_arg(1));

        // Set global template variables
        extract($this->_global_vars());

        // Require the requested view within the scope of these variables
        require func_get_arg(0);
    }

    // Public function to display views with data
    public function display($view, $data)
    {
        if(file_exists("views/$view.php"))
        {
            $this->_require("views/$view.php", $data);
        }

        return false;
    }
}