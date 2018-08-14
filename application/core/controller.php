<?php
/**
 * Class Controller
 * 
 * @property class $model functions for operations
 */
class Controller {
	
    public $model;
    public $view;

    function __construct()
    {
        $this->model = new Model();
        $this->view = new View();
    }


    function action_index()
    {
            
    }
}