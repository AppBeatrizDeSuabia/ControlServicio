<?php

namespace App\View\Components\bathroom_permissions;

use Illuminate\View\Component;

class history extends Component
{
    public $permissions;
    public $bathrooms;

    public function __construct($permissions, $bathrooms)
    {
        $this->permissions = $permissions;
        $this->bathrooms = $bathrooms;
    }

    public function render()
    {
        return view('components.bathroom_permissions.history');
    }
}