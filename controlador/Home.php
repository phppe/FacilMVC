<?php

namespace controlador;

class Home extends Modulo {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        Facil::despachar("index.html");
    }
    
    public function phpinfo() {
        phpinfo();
    }

}
