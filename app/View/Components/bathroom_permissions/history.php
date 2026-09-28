<?php

namespace App\View\Components\bathroom_permissions;

use Illuminate\View\Component;

class history extends Component
{
    public $permissions;
    public $bathrooms;
    public $courses;
    public $alumns;
    public $courseId;
    public $alumnId;
    public $selectedBathroom;

    public function __construct(
        $permissions,
        $bathrooms,
        $courses,
        $alumns,
        $courseId,
        $alumnId,
        $selectedBathroom
    ) {
        $this->permissions = $permissions;
        $this->bathrooms = $bathrooms;
        $this->courses = $courses;
        $this->alumns = $alumns;
        $this->courseId = $courseId;
        $this->alumnId = $alumnId;
        $this->selectedBathroom = $selectedBathroom;
    }

    public function render()
    {
        return view('components.bathroom_permissions.history');
    }
}