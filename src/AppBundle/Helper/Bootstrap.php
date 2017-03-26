<?php

namespace AppBundle\Helper;

class Bootstrap {

    public function boot()
    {
        GlobalVariables::generateUuid();
    }
}
